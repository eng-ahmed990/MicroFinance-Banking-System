document.addEventListener('DOMContentLoaded', function() {
    
    const badge = document.getElementById('kyc-badge');
    
    // Function to fetch pending count
    function fetchPendingCount() {
        fetch('api/get_pending_kyc.php') // Relative from admin pages
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateBadge(data.count);
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
    }

    // Function to update badge UI
    function updateBadge(count) {
        if (!badge) return;

        if (count > 0) {
            badge.style.display = 'inline-block';
            badge.innerText = count;
            // Optional: Add animation class
            badge.classList.add('pulse');
            setTimeout(() => badge.classList.remove('pulse'), 1000);
        } else {
            badge.style.display = 'none';
        }
    }

    // Poll every 10 seconds
    setInterval(fetchPendingCount, 10000);

    // Initial check
    fetchPendingCount();
});
