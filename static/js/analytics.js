document.addEventListener("DOMContentLoaded", () => {
    initAnalyticsDashboard();
});

/**
 * Orchestrates API ingestion requests and renders responses to UI layout blocks
 */
async function initAnalyticsDashboard() {
    const tableBody = document.getElementById("events-table-body");

    try {
        // Fetch raw event payload array straight from backend MySQLi feed
        const response = await fetch("api/get_events.php");
        
        if (!response.ok) {
            throw new Error(`HTTP transaction failure: Status ${response.status}`);
        }

        const events = await response.json();

        // Guard against empty array feeds
        if (!events || events.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="loading">No active event logs found in this workspace.</td></tr>`;
            updateGlobalKPIs(0, 0, 0);
            return;
        }

        // Initialize state track accumulators for runtime rollups
        let aggregateRegistrations = 0;
        let aggregateRevenue = 0;
        let aggregateCheckedIn = 0;

        // Reset the table frame before appending generated children items
        tableBody.innerHTML = "";

        // Loop over individual objects inside datasets array
        events.forEach(event => {
            // Safe fallback evaluation logic for missing data or strings
            const maxCapacity = parseInt(event.capacity) || 0;
            const registrations = parseInt(event.registeredUsersCount) || 0; 
            const checkedIn = parseInt(event.presentUsersCount) || 0;
            const revenue = parseFloat(event.revenue) || 0.00;

            // Runtime summary addition math transformations
            aggregateRegistrations += registrations;
            aggregateRevenue += revenue;
            aggregateCheckedIn += checkedIn;

            // Mathematical calculation of attendance rate per event
            const attendanceRate = registrations > 0 
                ? ((checkedIn / registrations) * 100).toFixed(1) 
                : "0.0";

            // Generate semantic table row markup safely
            const row = document.createElement("tr");
            row.innerHTML = `
                <td><strong>${escapeHtml(event.title)}</strong></td>
                <td>${escapeHtml(event.category || 'Uncategorized')}</td>
                <td>${registrations} / ${maxCapacity || '∞'}</td>
                <td>${formatCurrency(revenue)}</td>
                <td>${attendanceRate}%</td>
            `;
            tableBody.appendChild(row);
        });

        // Compute global attendance metric average conversion
        const globalAttendanceRate = aggregateRegistrations > 0 
            ? ((aggregateCheckedIn / aggregateRegistrations) * 100).toFixed(1) 
            : "0.0";

        // Bind global aggregated state updates straight to DOM hook tags
        updateGlobalKPIs(aggregateRegistrations, aggregateRevenue, globalAttendanceRate);

    } catch (error) {
        console.error("Analytics stream breakdown execution halted:", error);
        tableBody.innerHTML = `<tr><td colspan="5" class="loading" style="color: #ef4444;">Failed to load analytics feed. Please verify engine status.</td></tr>`;
    }
}

/**
 * Updates the high-level KPI card element text strings directly
 */
function updateGlobalKPIs(registrations, revenue, attendanceRate) {
    document.getElementById("meta-registrations").textContent = registrations.toLocaleString();
    document.getElementById("meta-revenue").textContent = formatCurrency(revenue);
    document.getElementById("meta-attendance").textContent = `${attendanceRate}%`;
}

/**
 * Sanitizes incoming text entries dynamically to mitigate potential XSS
 */
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
}

/**
 * Formats standard numeric values cleanly into currency elements
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}