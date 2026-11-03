import json
import math
import os
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer


PROJECT_TYPES = {
    "house": ("New home", 1.00),
    "extension": ("Home extension", 0.88),
    "rental": ("Rental units", 1.08),
}
FINISHES = {
    "basic": ("Basic", 0.35, 0.72),
    "standard": ("Standard", 0.45, 1.00),
    "premium": ("Premium", 0.58, 1.28),
}


class ValidationError(ValueError):
    pass


def _number(value, label, minimum, maximum):
    if isinstance(value, bool):
        raise ValidationError(f"{label} must be a number.")
    try:
        result = float(value)
    except (TypeError, ValueError):
        raise ValidationError(f"{label} must be a number.") from None
    if not math.isfinite(result) or result < minimum or result > maximum:
        raise ValidationError(f"{label} must be between {minimum:g} and {maximum:g}.")
    return result


def calculate_estimate(source):
    if not isinstance(source, dict):
        raise ValidationError("Submit a valid estimate.")

    name = source.get("project_name", "")
    location = source.get("location", "")
    project_type = source.get("project_type")
    finish_level = source.get("finish_level")
    if not isinstance(name, str) or not isinstance(location, str):
        raise ValidationError("Project name and location must be text.")
    name = name.strip() or "Untitled project"
    location = location.strip()
    if len(name) > 100 or len(location) > 100:
        raise ValidationError("Project name and location must each be 100 characters or fewer.")
    if not location:
        raise ValidationError("Enter the project location.")
    if not isinstance(project_type, str) or project_type not in PROJECT_TYPES:
        raise ValidationError("Choose a valid project type and finish level.")
    if not isinstance(finish_level, str) or finish_level not in FINISHES:
        raise ValidationError("Choose a valid project type and finish level.")

    area = _number(source.get("floor_area"), "Floor area", 10, 20000)
    raw_floors = source.get("floors")
    if isinstance(raw_floors, bool) or str(raw_floors) not in {"1", "2", "3", "4", "5"}:
        raise ValidationError("Choose between 1 and 5 floors.")
    floors = int(raw_floors)
    transport_percent = _number(source.get("transport_percent", 8), "Transport percentage", 0, 30)
    contingency_percent = _number(source.get("contingency_percent", 10), "Contingency percentage", 0, 30)

    materials = source.get("materials")
    if not isinstance(materials, list) or not materials:
        raise ValidationError("No construction materials are available.")

    type_label, type_factor = PROJECT_TYPES[project_type]
    finish_label, labour_factor, paint_factor = FINISHES[finish_level]
    scope = area * floors * type_factor
    items = []
    materials_cost = 0.0
    seen_ids = set()
    for material in materials:
        if not isinstance(material, dict):
            raise ValidationError("Material data is invalid.")
        material_id = material.get("id")
        if isinstance(material_id, bool) or not isinstance(material_id, int) or material_id <= 0 or material_id in seen_ids:
            raise ValidationError("Material IDs must be unique positive integers.")
        seen_ids.add(material_id)
        name_value = material.get("name")
        unit = material.get("unit")
        if not isinstance(name_value, str) or not isinstance(unit, str):
            raise ValidationError("Material names and units must be text.")
        factor = _number(material.get("quantity_factor"), "Material quantity factor", 0, 100000)
        price = _number(material.get("price"), "Material price", 0, 10000000)
        finish_sensitive = material.get("finish_sensitive") in (True, 1, "1")
        quantity = round(scope * factor * (paint_factor if finish_sensitive else 1), 2)
        if quantity <= 0:
            continue
        cost = round(quantity * price, 2)
        items.append({
            "material_id": material_id,
            "name": name_value,
            "unit": unit,
            "quantity": quantity,
            "unit_price": price,
            "total_cost": cost,
        })
        materials_cost += cost

    materials_cost = round(materials_cost, 2)
    labour_cost = round(materials_cost * labour_factor, 2)
    transport_cost = round(materials_cost * transport_percent / 100, 2)
    contingency_cost = round((materials_cost + labour_cost + transport_cost) * contingency_percent / 100, 2)
    total_cost = round(materials_cost + labour_cost + transport_cost + contingency_cost, 2)
    return {
        "input": {
            "project_name": name,
            "location": location,
            "project_type": project_type,
            "floor_area": area,
            "floors": floors,
            "finish_level": finish_level,
            "transport_percent": transport_percent,
            "contingency_percent": contingency_percent,
        },
        "items": items,
        "materials_cost": materials_cost,
        "labour_cost": labour_cost,
        "transport_cost": transport_cost,
        "contingency_cost": contingency_cost,
        "total_cost": total_cost,
        "project_type_label": type_label,
        "finish_label": finish_label,
    }


class CalculatorHandler(BaseHTTPRequestHandler):
    def _send(self, status, payload):
        body = json.dumps(payload).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Cache-Control", "no-store")
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self):
        if self.path == "/health":
            return self._send(200, {"status": "ok"})
        return self._send(404, {"error": "Not found."})

    def do_POST(self):
        if self.path != "/calculate":
            return self._send(404, {"error": "Not found."})
        try:
            try:
                length = int(self.headers.get("Content-Length", "0"))
            except ValueError:
                raise ValidationError("Request body must have a valid content length.") from None
            if length <= 0 or length > 1024 * 1024:
                raise ValidationError("Request body must be between 1 byte and 1 MB.")
            payload = json.loads(self.rfile.read(length))
            return self._send(200, calculate_estimate(payload))
        except (json.JSONDecodeError, UnicodeDecodeError):
            return self._send(400, {"error": "Request body must contain valid JSON."})
        except ValidationError as error:
            return self._send(400, {"error": str(error)})

    def log_message(self, format_string, *args):
        print(f"{self.address_string()} - {format_string % args}")


if __name__ == "__main__":
    port = int(os.environ.get("PORT", "8001"))
    server = ThreadingHTTPServer(("0.0.0.0", port), CalculatorHandler)
    print(f"Estimate calculator listening on port {port}", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()
