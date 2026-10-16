const byId = (id) => document.getElementById(id);
const config = window.FLEETCARE;
let data = { vehicles: [], maintenance: [], fuel: [], documents: [], summary: {} };

function escapeHtml(value) {
  return String(value ?? "").replace(/[&<>"']/g, (character) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  })[character]);
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString("en-KE", { maximumFractionDigits: 2 });
}

function formatMoney(value) {
  return `${config.currency} ${formatNumber(value)}`;
}

function localDate(dateValue) {
  const [year, month, day] = String(dateValue).slice(0, 10).split("-").map(Number);
  return new Date(year, month - 1, day);
}

function formattedDate(dateValue) {
  return localDate(dateValue).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
}

function monthLabel(dateValue) {
  return localDate(dateValue).toLocaleDateString("en-GB", { month: "short" }).toUpperCase();
}

function typeClass(type) {
  return String(type).toLowerCase().replace(/[^a-z]/g, "");
}

async function apiRequest(resource, payload) {
  const options = payload
    ? {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": config.csrfToken },
        body: JSON.stringify(payload),
      }
    : { headers: { Accept: "application/json" } };
  const response = await fetch(`api.php${payload ? "" : `?resource=${encodeURIComponent(resource)}`}`, options);
  let result;
  try {
    result = await response.json();
  } catch {
    throw new Error("FleetCare could not read the server response. Check the PHP error log and database setup.");
  }
  if (!response.ok) {
    if (response.status === 401) window.location.assign("login.php");
    throw new Error(result.error || "The request could not be completed.");
  }
  return result.data;
}

async function refreshData() {
  data = await apiRequest("dashboard");
  renderAll();
}

function vehicleIcon() {
  return '<svg class="icon"><use href="#i-car"/></svg>';
}

function vehicleRow(vehicle) {
  const status = vehicle.status || "Active";
  const statusClass = status === "Active" ? "active" : status === "In service" ? "inactive" : "service";
  const remaining = vehicle.service === null ? null : Math.max(0, Number(vehicle.service) - Number(vehicle.odometer));
  const serviceText = remaining === null ? "Not set" : `${formatNumber(remaining)} km`;
  const soon = remaining !== null && remaining <= 1000 && status !== "In service" ? "soon" : "";
  return `<tr>
    <td><div class="vehicle-cell"><span class="vehicle-thumb ${typeClass(vehicle.type)}">${vehicleIcon()}</span><span class="vehicle-name"><strong>${escapeHtml(vehicle.name)}</strong><small>${escapeHtml(vehicle.registration)} · ${escapeHtml(vehicle.type)}</small></span></div></td>
    <td><span class="status-badge ${statusClass}">${escapeHtml(status)}</span></td>
    <td>${formatNumber(vehicle.odometer)} km</td>
    <td><span class="service-distance ${soon}">${serviceText}</span></td>
    <td><button class="icon-button row-menu" data-vehicle-menu="${escapeHtml(vehicle.id)}" aria-label="Vehicle details"><svg class="icon"><use href="#i-more"/></svg></button></td>
  </tr>`;
}

function renderVehicles() {
  const rows = data.vehicles.map(vehicleRow);
  byId("fleet-table-body").innerHTML = rows.slice(0, 5).join("");
  const query = (byId("vehicle-filter")?.value || "").trim().toLowerCase();
  const filtered = data.vehicles.filter((vehicle) =>
    `${vehicle.name} ${vehicle.registration} ${vehicle.type} ${vehicle.status}`.toLowerCase().includes(query));
  byId("vehicles-table-body").innerHTML = filtered.map(vehicleRow).join("");
  byId("vehicle-empty").classList.toggle("hidden", filtered.length > 0);
  byId("metric-vehicles").textContent = String(data.summary.vehicleCount || 0).padStart(2, "0");
  byId("active-vehicle-count").textContent = `${data.summary.activeVehicles || 0} active`;
  byId("vehicle-nav-count").textContent = data.summary.vehicleCount || 0;
  byId("vehicles-view-count").textContent = data.summary.vehicleCount || 0;
  byId("maintenance-nav-count").textContent = data.maintenance.filter((record) => record.status === "Scheduled").length;
  byId("table-count").textContent = `Showing ${Math.min(5, data.vehicles.length)} of ${data.vehicles.length} vehicles`;
  const options = data.vehicles.map((vehicle) =>
    `<option value="${escapeHtml(vehicle.id)}">${escapeHtml(vehicle.name)} (${escapeHtml(vehicle.registration)})</option>`).join("");
  ["maintenance-vehicle", "fuel-vehicle", "document-vehicle"].forEach((id) => { byId(id).innerHTML = options; });
}

function dueLabel(days) {
  const value = Number(days);
  if (value < 0) return `Overdue ${Math.abs(value)}d`;
  if (value === 0) return "Due today";
  return value === 1 ? "In 1 day" : `In ${value} days`;
}

function renderReminders() {
  const maintenanceReminders = data.maintenance
    .filter((record) => record.status === "Scheduled")
    .map((record) => ({
      id: record.id,
      source: "maintenance",
      date: record.dueDate,
      vehicle: record.vehicle,
      registration: record.registration,
      task: record.task,
      days: Number(record.daysUntil),
      urgent: Number(record.daysUntil) <= 3,
    }));
  const documentReminders = data.documents
    .filter((document) => Number(document.daysUntil) <= 60)
    .map((document) => ({
      id: document.id,
      source: "document",
      date: document.expiryDate,
      vehicle: document.vehicle,
      registration: document.registration,
      task: `${document.type} renewal`,
      days: Number(document.daysUntil),
      urgent: Number(document.daysUntil) <= 14,
    }));
  const mileageReminders = (data.mileageAlerts || []).map((alert) => ({
    source: "mileage",
    date: alert.overdue ? "0000-00-00" : "0000-00-01",
    vehicle: alert.vehicle,
    registration: alert.registration,
    task: alert.overdue ? "Service overdue" : "Service coming up",
    detail: alert.overdue
      ? `${formatNumber(Math.abs(alert.remainingKm))} km past service`
      : `Within ${formatNumber(alert.remainingKm)} km`,
    urgent: alert.overdue,
    mileage: true,
  }));
  const reminders = [...mileageReminders, ...maintenanceReminders, ...documentReminders]
    .sort((a, b) => {
      if (a.mileage !== b.mileage) return a.mileage ? -1 : 1;
      return a.date.localeCompare(b.date);
    });

  byId("reminder-list").innerHTML = reminders.slice(0, 4).map((reminder) => `
    <div class="reminder-item">
      <span class="reminder-date ${reminder.urgent ? "overdue" : reminder.mileage ? "mileage" : ""}"><strong>${reminder.mileage ? "KM" : localDate(reminder.date).getDate()}</strong><small>${reminder.mileage ? "SERVICE" : monthLabel(reminder.date)}</small></span>
      <span class="reminder-copy"><strong>${escapeHtml(reminder.task)}</strong><small>${escapeHtml(reminder.vehicle)} · ${escapeHtml(reminder.registration)}</small></span>
      <span class="reminder-tag ${reminder.urgent ? "overdue-tag" : reminder.mileage ? "mileage-tag" : ""}">${reminder.mileage ? escapeHtml(reminder.detail) : escapeHtml(dueLabel(reminder.days))}</span>
    </div>`).join("") || '<p class="empty-state">No upcoming reminders. Add a service or document expiry.</p>';

  const scheduled = data.maintenance
    .filter((record) => record.status === "Scheduled")
    .sort((a, b) => a.dueDate.localeCompare(b.dueDate));
  byId("schedule-list").innerHTML = scheduled.map((record) => `
    <div class="schedule-item ${Number(record.daysUntil) <= 3 ? "urgent" : ""}">
      <span class="schedule-date"><strong>${localDate(record.dueDate).getDate()}</strong><small>${monthLabel(record.dueDate)}</small></span>
      <span class="schedule-vehicle"><strong>${escapeHtml(record.task)}</strong><small>${escapeHtml(record.vehicle)} · ${escapeHtml(record.registration)}</small></span>
      <span class="schedule-type"><strong>Scheduled service</strong><small>${escapeHtml(dueLabel(record.daysUntil))}</small></span>
      <span class="schedule-assignee">${record.cost > 0 ? formatMoney(record.cost) : "Fleet team"}</span>
      <button class="schedule-actions" data-maintenance-done="${escapeHtml(record.id)}" aria-label="Mark service complete"><svg class="icon"><use href="#i-check"/></svg></button>
    </div>`).join("") || '<p class="empty-state">Nothing scheduled. Add a service reminder to get started.</p>';
  const completed = data.maintenance
    .filter((record) => record.status === "Completed")
    .sort((a, b) => b.dueDate.localeCompare(a.dueDate))
    .slice(0, 10);
  byId("maintenance-history").innerHTML = completed.map((record) => `
    <div class="schedule-item">
      <span class="schedule-date"><strong>✓</strong><small>DONE</small></span>
      <span class="schedule-vehicle"><strong>${escapeHtml(record.task)}</strong><small>${escapeHtml(record.vehicle)} · ${escapeHtml(record.registration)}</small></span>
      <span class="schedule-type"><strong>${formattedDate(record.dueDate)}</strong><small>Completed</small></span>
      <span class="schedule-assignee">${formatMoney(record.cost)}</span>
      <span></span>
    </div>`).join("") || '<p class="empty-state">Completed maintenance records will appear here.</p>';
  byId("maintenance-due-count").textContent = scheduled.length;
  byId("maintenance-completed-count").textContent = data.maintenance.filter((record) => record.status === "Completed").length;
}

function renderFuel() {
  const distances = new Map();
  const previousOdometer = new Map();
  [...data.fuel].reverse().forEach((entry) => {
    const previous = previousOdometer.get(String(entry.vehicleId));
    distances.set(String(entry.id), previous === undefined ? null : Math.max(0, Number(entry.odometer) - previous));
    previousOdometer.set(String(entry.vehicleId), Number(entry.odometer));
  });
  byId("fuel-table-body").innerHTML = data.fuel.map((entry) => {
    const distance = distances.get(String(entry.id));
    return `<tr>
      <td><div class="vehicle-name"><strong>${escapeHtml(entry.vehicle)}</strong><small>${escapeHtml(entry.registration)}</small></div></td>
      <td>${formattedDate(entry.date)}</td>
      <td>${formatNumber(entry.litres)} L</td>
      <td>${distance === null ? "—" : `${formatNumber(distance)} km`}</td>
      <td class="fuel-amount">${formatMoney(entry.amount)}</td>
    </tr>`;
  }).join("");
  byId("fuel-empty").classList.toggle("hidden", data.fuel.length > 0);
  byId("fuel-entry-count").textContent = data.fuel.length;
  byId("fuel-page-spend").textContent = formatNumber(data.summary.fuelSpend);
  byId("metric-fuel").textContent = formatNumber(data.summary.fuelSpend);
  byId("fuel-page-distance").textContent = formatNumber(data.summary.distance);
  byId("fuel-month-label").textContent = new Date().toLocaleDateString("en-GB", { month: "long", year: "numeric" });
}

function renderDocuments() {
  const documents = [...data.documents].sort((a, b) => a.expiryDate.localeCompare(b.expiryDate));
  byId("documents-count").textContent = documents.length;
  byId("documents-table-body").innerHTML = documents.map((document) => {
    const days = Number(document.daysUntil);
    const status = days < 0 ? `Overdue · ${dueLabel(days)}`
      : days <= 7 ? `Due within 7 days · ${dueLabel(days)}`
        : days <= 14 ? `Due within 14 days · ${dueLabel(days)}`
          : days <= 30 ? `Due within 30 days · ${dueLabel(days)}` : "Up to date";
    const statusClass = days < 0 || days <= 7 ? "overdue"
      : days <= 30 ? "service" : "active";
    return `<tr>
      <td><div class="vehicle-name"><strong>${escapeHtml(document.type)}</strong><small>${escapeHtml(document.notes || "Vehicle document")}</small></div></td>
      <td><div class="vehicle-name"><strong>${escapeHtml(document.vehicle)}</strong><small>${escapeHtml(document.registration)}</small></div></td>
      <td>${escapeHtml(document.documentNumber || "—")}</td>
      <td>${formattedDate(document.expiryDate)}</td>
      <td><span class="status-badge ${statusClass}">${escapeHtml(status)}</span></td>
    </tr>`;
  }).join("");
  byId("documents-empty").classList.toggle("hidden", documents.length > 0);
}

function renderSummary() {
  const summary = data.summary;
  byId("metric-spend").textContent = formatNumber(summary.maintenanceSpend);
  byId("maintenance-spend").textContent = formatNumber(summary.maintenanceSpend);
  byId("metric-due").textContent = String(summary.dueSoon || 0).padStart(2, "0");
  byId("report-total-spend").textContent = formatNumber(Number(summary.maintenanceSpend) + Number(summary.fuelSpend));
  byId("report-maintenance-spend").textContent = formatNumber(summary.maintenanceSpend);
  const availability = summary.vehicleCount ? Math.round(summary.activeVehicles / summary.vehicleCount * 100) : 0;
  byId("report-availability").textContent = availability;
  byId("report-vehicle-count").textContent = `${summary.activeVehicles || 0} active of ${summary.vehicleCount || 0} vehicles`;
  byId("report-distance").textContent = formatNumber(summary.distance);
}

function renderReports() {
  const totals = (data.summary.monthlyCosts || []).map((entry) => ({
    month: localDate(`${entry.month}-01`),
    fuel: Number(entry.fuel),
    maintenance: Number(entry.maintenance),
  }));
  const largest = Math.max(0, ...totals.map((entry) => entry.fuel + entry.maintenance));
  const scale = Math.max(50000, Math.ceil(largest / 50000) * 50000);
  byId("chart-grid").innerHTML = [1, .75, .5, .25, 0].map((ratio) =>
    `<i>${escapeHtml(config.currency)} ${formatNumber(scale * ratio)}</i>`).join("");
  byId("chart-columns").innerHTML = totals.map((entry, index) => {
    const fuelHeight = Math.max(entry.fuel ? 2 : 0, entry.fuel / scale * 100);
    const maintenanceHeight = Math.max(entry.maintenance ? 2 : 0, entry.maintenance / scale * 100);
    const monthName = entry.month.toLocaleDateString("en-GB", { month: "short" });
    return `<div class="chart-month ${index === totals.length - 1 ? "current-month" : ""}">
      <div class="bars"><i title="Maintenance ${escapeHtml(formatMoney(entry.maintenance))}" style="height:${maintenanceHeight}%"></i><i title="Fuel ${escapeHtml(formatMoney(entry.fuel))}" style="height:${fuelHeight}%"></i></div>
      <span>${escapeHtml(monthName)}</span>
    </div>`;
  }).join("");

  const fuel = Number(data.summary.fuelSpend || 0);
  const maintenance = Number(data.summary.maintenanceSpend || 0);
  const total = fuel + maintenance;
  const fuelPercent = total ? Math.round(fuel / total * 100) : 0;
  const maintenancePercent = total ? 100 - fuelPercent : 0;
  byId("cost-donut").classList.toggle("is-empty", total === 0);
  byId("cost-donut").style.background = total
    ? `conic-gradient(#cbdcc8 0 ${fuelPercent}%, #6b9e73 ${fuelPercent}% 100%)`
    : "";
  byId("donut-total").textContent = formatMoney(total);
  byId("breakdown-fuel").textContent = formatMoney(fuel);
  byId("breakdown-maintenance").textContent = formatMoney(maintenance);
  byId("breakdown-fuel-percent").textContent = `${fuelPercent}%`;
  byId("breakdown-maintenance-percent").textContent = `${maintenancePercent}%`;
  const vehicleCosts = [...(data.summary.vehicleCosts || [])]
    .sort((a, b) => {
      const aCost = a.costPerKm === null ? -1 : Number(a.costPerKm);
      const bCost = b.costPerKm === null ? -1 : Number(b.costPerKm);
      return bCost - aCost;
    });
  byId("vehicle-cost-month").textContent = new Date().toLocaleDateString("en-GB", { month: "long", year: "numeric" });
  byId("vehicle-cost-table").innerHTML = vehicleCosts.map((vehicle) => `
    <tr>
      <td><div class="vehicle-name"><strong>${escapeHtml(vehicle.vehicle)}</strong><small>${escapeHtml(vehicle.registration)}</small></div></td>
      <td>${formatMoney(vehicle.fuelSpend)}</td>
      <td>${formatMoney(vehicle.maintenanceSpend)}</td>
      <td>${formatNumber(vehicle.distance)} km</td>
      <td>${vehicle.costPerKm === null ? "—" : `${formatMoney(vehicle.costPerKm)} / km`}</td>
    </tr>`).join("");
  byId("vehicle-cost-empty").classList.toggle("hidden", vehicleCosts.length > 0);
}

function renderAll() {
  renderVehicles();
  renderReminders();
  renderFuel();
  renderDocuments();
  renderSummary();
  renderReports();
}

function showToast(message, error = false) {
  const toast = document.createElement("div");
  toast.className = `toast${error ? " error" : ""}`;
  toast.innerHTML = `<svg class="icon"><use href="${error ? "#i-alert" : "#i-check"}"/></svg><span>${escapeHtml(message)}</span>`;
  byId("toast-region").append(toast);
  window.setTimeout(() => toast.remove(), 3600);
}

function exportCsv() {
  const headers = ["Vehicle", "Registration", "Type", "Status", "Odometer (km)", "Next service (km)"];
  const lines = data.vehicles.map((vehicle) => [
    vehicle.name, vehicle.registration, vehicle.type, vehicle.status, vehicle.odometer, vehicle.service ?? "",
  ].map((value) => `"${String(value).replace(/"/g, '""')}"`).join(","));
  const csv = [headers.join(","), ...lines].join("\r\n");
  const url = URL.createObjectURL(new Blob([csv], { type: "text/csv;charset=utf-8" }));
  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = "fleetcare-vehicle-report.csv";
  document.body.append(anchor);
  anchor.click();
  anchor.remove();
  URL.revokeObjectURL(url);
  showToast("Your fleet report has been downloaded.");
}

function setView(view, updateHistory = true) {
  const target = document.querySelector(`[data-page="${view}"]`);
  if (!target) return;
  document.querySelectorAll(".view-panel").forEach((panel) => panel.classList.toggle("active", panel === target));
  document.querySelectorAll(".nav-item[data-view]").forEach((link) => link.classList.toggle("active", link.dataset.view === view));
  const heading = target.querySelector("h1")?.textContent.trim() || "Overview";
  byId("breadcrumb-current").textContent = view === "dashboard" ? "Overview" : heading;
  document.title = `FleetCare EA — ${view === "dashboard" ? "Fleet overview" : heading}`;
  if (updateHistory && window.location.hash !== `#${view}`) window.history.pushState({ view }, "", `#${view}`);
  closeMobileNav();
}

function closeMobileNav(restoreFocus = false) {
  byId("sidebar").classList.remove("open");
  byId("sidebar-backdrop").classList.remove("is-visible");
  byId("sidebar-backdrop").setAttribute("aria-hidden", "true");
  byId("sidebar-backdrop").setAttribute("tabindex", "-1");
  byId("mobile-menu").setAttribute("aria-expanded", "false");
  byId("mobile-menu").setAttribute("aria-label", "Open navigation");
  byId("mobile-menu").querySelector("use").setAttribute("href", "#i-menu");
  document.body.classList.remove("mobile-nav-open");
  if (restoreFocus) byId("mobile-menu").focus();
}

function toggleMobileNav() {
  const isOpen = byId("sidebar").classList.toggle("open");
  byId("sidebar-backdrop").classList.toggle("is-visible", isOpen);
  byId("sidebar-backdrop").setAttribute("aria-hidden", String(!isOpen));
  byId("sidebar-backdrop").setAttribute("tabindex", isOpen ? "0" : "-1");
  byId("mobile-menu").setAttribute("aria-expanded", String(isOpen));
  byId("mobile-menu").setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
  byId("mobile-menu").querySelector("use").setAttribute("href", isOpen ? "#i-close" : "#i-menu");
  document.body.classList.toggle("mobile-nav-open", isOpen);
  if (isOpen) byId("sidebar").querySelector(".primary-nav .nav-item")?.focus();
}

function openDialog(id) {
  if (data.vehicles.length === 0 && id !== "vehicle-modal") {
    showToast("Add a vehicle to your fleet first.", true);
    return;
  }
  byId(id).showModal();
}

async function submitAction(form, action, values, successMessage) {
  try {
    await apiRequest("", { action, ...values });
    form.reset();
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
    if (form.id === "maintenance-form") form.elements.serviceDate.value = today;
    if (form.id === "fuel-form") form.elements.filledAt.value = today;
    form.closest("dialog").close();
    await refreshData();
    showToast(successMessage);
  } catch (error) {
    showToast(error.message, true);
  }
}

function bindEvents() {
  document.querySelectorAll("[data-view]").forEach((link) => link.addEventListener("click", (event) => {
    event.preventDefault();
    setView(link.dataset.view);
  }));
  document.querySelectorAll("[data-open-modal]").forEach((button) =>
    button.addEventListener("click", () => openDialog(button.dataset.openModal)));
  document.querySelectorAll(".close-dialog").forEach((button) =>
    button.addEventListener("click", () => button.closest("dialog").close()));
  document.querySelectorAll("dialog").forEach((dialog) => dialog.addEventListener("click", (event) => {
    if (event.target === dialog) dialog.close();
  }));

  byId("vehicle-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form));
    await submitAction(form, "create_vehicle", {
      ...values,
      name: String(values.name).trim(),
      registration: String(values.registration).trim().toUpperCase(),
      odometer: Number(values.odometer),
      service: values.service === "" ? null : Number(values.service),
    }, "Vehicle added to your fleet.");
  });

  byId("maintenance-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form));
    await submitAction(form, "create_maintenance", {
      ...values,
      vehicleId: Number(values.vehicleId),
      cost: Number(values.cost),
      odometer: values.odometer === "" ? null : Number(values.odometer),
      nextServiceOdometer: values.nextServiceOdometer === "" ? null : Number(values.nextServiceOdometer),
    }, values.status === "Completed" ? "Maintenance record saved." : "Service reminder saved.");
  });

  byId("fuel-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form));
    await submitAction(form, "create_fuel", {
      ...values,
      vehicleId: Number(values.vehicleId),
      litres: Number(values.litres),
      pricePerLitre: Number(values.pricePerLitre),
      odometer: Number(values.odometer),
    }, "Fuel entry saved and odometer updated.");
  });

  byId("document-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form));
    await submitAction(form, "create_document", { ...values, vehicleId: Number(values.vehicleId) }, "Vehicle document saved.");
  });

  byId("vehicle-filter").addEventListener("input", renderVehicles);
  byId("global-search").addEventListener("input", (event) => {
    const query = event.currentTarget.value.trim().toLowerCase();
    if (query) {
      setView("vehicles", false);
      byId("vehicle-filter").value = query;
    } else {
      byId("vehicle-filter").value = "";
    }
    renderVehicles();
  });
  byId("global-search").addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      event.currentTarget.value = "";
      byId("vehicle-filter").value = "";
      renderVehicles();
      event.currentTarget.blur();
    }
  });
  document.addEventListener("keydown", (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === "k") {
      event.preventDefault();
      byId("global-search").focus();
    }
  });

  ["export-csv", "fuel-export", "reports-export"].forEach((id) => byId(id).addEventListener("click", exportCsv));
  byId("view-all-vehicles").addEventListener("click", () => setView("vehicles"));
  byId("mobile-menu").addEventListener("click", toggleMobileNav);
  byId("sidebar-backdrop").addEventListener("click", () => closeMobileNav(true));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && byId("sidebar").classList.contains("open")) closeMobileNav(true);
  });
  byId("notifications").addEventListener("click", () => {
    setView("maintenance");
    showToast(`${data.summary.dueSoon || 0} services and documents need attention soon.`);
  });
  byId("reminders-menu").addEventListener("click", () => showToast("Reminders are sorted by their due date."));

  document.addEventListener("click", async (event) => {
    const button = event.target.closest("[data-maintenance-done]");
    if (button) {
      try {
        await apiRequest("", { action: "complete_maintenance", id: Number(button.dataset.maintenanceDone) });
        await refreshData();
        showToast("Service marked complete.");
      } catch (error) {
        showToast(error.message, true);
      }
      return;
    }
    const vehicleButton = event.target.closest("[data-vehicle-menu]");
    if (vehicleButton) {
      const vehicle = data.vehicles.find((item) => String(item.id) === vehicleButton.dataset.vehicleMenu);
      if (vehicle) showToast(`${vehicle.name}: ${formatNumber(vehicle.odometer)} km on the odometer.`);
    }
  });

  byId("settings-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
      const result = await apiRequest("", {
        action: "update_settings",
        workspace: byId("workspace-name").value.trim(),
        currency: byId("currency-setting").value,
      });
      config.workspace = result.workspace;
      config.currency = result.currency;
      document.querySelectorAll(".workspace-copy strong, .breadcrumb > span").forEach((element) => {
        element.textContent = result.workspace;
      });
      document.querySelectorAll(".currency-code").forEach((element) => { element.textContent = result.currency; });
      await refreshData();
      showToast("Workspace settings saved.");
    } catch (error) {
      showToast(error.message, true);
    }
  });

  window.addEventListener("popstate", () => setView(window.location.hash.slice(1) || "dashboard", false));
}

async function initialize() {
  const today = new Date();
  byId("current-date").textContent = today.toLocaleDateString("en-GB", {
    weekday: "long", day: "numeric", month: "long", year: "numeric",
  }).toUpperCase();
  byId("workspace-name").value = config.workspace;
  const todayValue = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
  byId("fuel-form").elements.filledAt.value = todayValue;
  byId("document-form").elements.expiryDate.value = "";
  byId("maintenance-form").elements.serviceDate.value = todayValue;
  bindEvents();
  const initialView = window.location.hash.slice(1);
  if (initialView) setView(initialView, false);
  try {
    await refreshData();
  } catch (error) {
    showToast(error.message, true);
  }
}

initialize();
