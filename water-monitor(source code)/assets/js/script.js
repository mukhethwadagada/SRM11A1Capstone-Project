// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle leak alerts
    if (window.location.search.includes('leak=1')) {
        showLeakAlert();
    }
});

// Show leak alert modal
function showLeakAlert() {
    const alertHTML = `
        <div class="modal fade" id="leakAlertModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">🚨 Potential Leak Detected</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Your latest water reading shows unusually high usage that could indicate a leak.</p>
                        <div class="alert alert-warning">
                            <strong>Recommended actions:</strong>
                            <ul class="mb-0">
                                <li>Check all faucets and toilets for running water</li>
                                <li>Inspect pipes for visible leaks</li>
                                <li>Contact a plumber if you can't locate the issue</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Dismiss</button>
                        <a href="tips.php" class="btn btn-danger">View Leak Fixing Tips</a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    const modal = new bootstrap.Modal(document.getElementById('leakAlertModal'));
    modal.show();
}

// Water usage prediction
function predictUsage() {
    const form = document.getElementById('predictionForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const days = parseInt(document.getElementById('predictionDays').value);
        
        // Simulate prediction (in real app, would call API)
        const prediction = days * 120; // Mock data
        
        document.getElementById('predictionResult').innerHTML = `
            <div class="alert alert-info mt-3">
                Estimated usage in ${days} days: <strong>${prediction} liters</strong>
            </div>
        `;
    });
}

// Initialize chart
function initUsageChart() {
    const ctx = document.getElementById('usageChart');
    if (!ctx) return;
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Your Usage (Liters)',
                data: [120, 135, 150, 110, 180, 200, 90],
                borderColor: 'rgba(0, 123, 255, 1)',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Neighborhood Average',
                data: [130, 125, 130, 120, 140, 160, 110],
                borderColor: 'rgba(108, 117, 125, 1)',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                tension: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Liters'
                    }
                }
            }
        }
    });
}

// Initialize all functions when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    initUsageChart();
    predictUsage();
    
    // Handle photo preview
    const photoInput = document.getElementById('meter_photo');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('photoPreview');
                    if (!preview) {
                        const previewHTML = `
                            <div id="photoPreview" class="mt-3">
                                <img src="${event.target.result}" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        `;
                        photoInput.insertAdjacentHTML('afterend', previewHTML);
                    } else {
                        preview.innerHTML = `<img src="${event.target.result}" class="img-thumbnail" style="max-height: 200px;">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});