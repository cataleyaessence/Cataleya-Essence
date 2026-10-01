document.addEventListener('DOMContentLoaded', () => {
    const page = document.body;
    const liveStatus = document.getElementById('customerRankingLiveStatus');
    let snapshot = page.dataset.rankingSnapshot || '';
    let checking = false;

    const setStatus = (message, isOffline = false) => {
        if (!liveStatus) {
            return;
        }

        liveStatus.classList.toggle('is-offline', isOffline);
        liveStatus.lastChild.textContent = ` ${message}`;
    };

    const checkForUpdates = async () => {
        if (checking || document.hidden) {
            return;
        }

        checking = true;
        try {
            const response = await fetch('Admin-CustomerRanking.php?action=snapshot', {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

            if (!response.ok || !data.success || !data.signature) {
                throw new Error('Could not get the latest ranking.');
            }

            if (snapshot && snapshot !== data.signature) {
                setStatus('Updating ranking…');
                window.location.reload();
                return;
            }

            snapshot = data.signature;
            setStatus('Live updates are on');
        } catch (error) {
            setStatus('Live update will retry', true);
        } finally {
            checking = false;
        }
    };

    window.setInterval(checkForUpdates, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            checkForUpdates();
        }
    });
});
