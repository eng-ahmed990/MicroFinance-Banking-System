/**
 * Live Updates Script
 * Polls the server every 5 seconds for notifications and data updates.
 */

(function () {
    const POLL_INTERVAL = 3000; // 3 seconds

    function fetchUpdates() {
        // Adjust path based on current location (admin vs user vs root)
        // Assuming we are in /user/ or /admin/, API is in ../api/
        // If in root, API is in ./api/
        let apiPath = '../api/poll_updates.php';
        if (window.location.pathname.endsWith('MicroFinance/') || window.location.pathname.endsWith('index.php')) {
            apiPath = 'api/poll_updates.php';
        }

        fetch(apiPath)
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                if (data.error) return; // Likely not logged in

                // 1. Update Notification Badge
                const badge = document.getElementById('notify-badge');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.style.display = 'flex';
                        badge.innerText = data.unread_count;
                    } else {
                        badge.style.display = 'none';
                    }
                }

                // 2. Update Dashboard Stats (if elements exist)
                updateStat('stat-balance', formatMoney(data.loan_balance));
                updateStat('stat-next-payment', formatMoney(data.next_payment));
                updateStat('stat-status', data.loan_status);
                updateStat('stat-total-paid', formatMoney(data.total_paid));

            })
            .catch(err => console.log('Polling paused:', err));
    }

    function updateStat(id, value) {
        const el = document.getElementById(id);
        if (el && el.innerText !== value) {
            // Add flash effect? 
            el.innerText = value;
        }
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    // Start Polling
    setInterval(fetchUpdates, POLL_INTERVAL);
    // Initial fetch
    fetchUpdates();

})();
