import unittest

from calculator import ValidationError, calculate_estimate


MATERIALS = [
    {"id": 1, "name": "Cement", "unit": "bag", "quantity_factor": 0.42, "finish_sensitive": 0, "price": 850},
    {"id": 2, "name": "Interior paint", "unit": "litre", "quantity_factor": 0.25, "finish_sensitive": 1, "price": 450},
]


def estimate(**overrides):
    source = {
        "project_name": "",
        "location": "Kiambu",
        "project_type": "house",
        "floor_area": "120",
        "floors": "1",
        "finish_level": "standard",
        "transport_percent": "8",
        "contingency_percent": "10",
        "materials": MATERIALS,
    }
    source.update(overrides)
    return calculate_estimate(source)


class CalculatorTests(unittest.TestCase):
    def test_calculates_costs_and_finish_sensitive_quantities(self):
        result = estimate()
        self.assertEqual(result["input"]["project_name"], "Untitled project")
        self.assertEqual(result["items"][0]["quantity"], 50.4)
        self.assertEqual(result["items"][1]["quantity"], 30.0)
        self.assertEqual(result["materials_cost"], 56340.0)
        self.assertEqual(result["total_cost"], 94820.22)

    def test_rejects_out_of_range_area(self):
        with self.assertRaisesRegex(ValidationError, "Floor area"):
            estimate(floor_area="20001")

    def test_rejects_non_integer_floor_count(self):
        with self.assertRaisesRegex(ValidationError, "floors"):
            estimate(floors="1.5")

    def test_rejects_non_finite_allowance(self):
        with self.assertRaisesRegex(ValidationError, "Transport"):
            estimate(transport_percent="NaN")

    def test_applies_extension_factor(self):
        result = estimate(project_type="extension")
        self.assertEqual(result["items"][0]["quantity"], 44.35)
        self.assertEqual(result["project_type_label"], "Home extension")

    def test_rejects_non_text_project_type(self):
        with self.assertRaisesRegex(ValidationError, "project type"):
            estimate(project_type=[])


if __name__ == "__main__":
    unittest.main()
