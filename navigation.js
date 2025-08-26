// Navigation functionality for UniWhisper
function setupNavigation() {
    const navButtons = document.querySelectorAll('.nav-btn');
    
    navButtons.forEach(button => {
        button.addEventListener('click', async (e) => { // Added async
            const buttonId = e.currentTarget.id;
            let sectionId = '';
            
            // Ensure uniWhisperApp is available before calling its methods
            if (!window.uniWhisperApp) {
                console.error("UniWhisper app instance not available.");
                return;
            }

            switch(buttonId) {
                case 'nav-home':
                    sectionId = 'home-section';
                    await window.uniWhisperApp.loadView('feed', true); // Use loadView for consistency
                    break;
                case 'nav-explore':
                    sectionId = 'explore-section';
                    await window.uniWhisperApp.loadView('explore', true); // Use loadView for consistency
                    break;
                case 'nav-notifications':
                    sectionId = 'notifications-section';
                    await window.uniWhisperApp.loadView('notifications', true); // Use loadView for consistency
                    break;
                case 'nav-profile':
                    sectionId = 'profile-section';
                    await window.uniWhisperApp.loadView('profile', true); // Use loadView for consistency
                    break;
            }
            
            showSection(sectionId);
            updateActiveNav(buttonId);
        });
    });
}

function showSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.section-content');
    sections.forEach(section => {
        section.classList.add('hidden');
    });
    
    // Show the selected section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.remove('hidden');
    }
}

function updateActiveNav(activeButtonId) {
    // Remove active state from all nav buttons
    const navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(button => {
        button.classList.remove('text-primary-600');
        button.classList.add('text-neutral-500');
    });
    
    // Add active state to clicked button
    const activeButton = document.getElementById(activeButtonId);
    if (activeButton) {
        activeButton.classList.remove('text-neutral-500');
        activeButton.classList.add('text-primary-600');
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        if (window.uniWhisperApp) {
            window.uniWhisperApp.showToast('Copied to clipboard!', 'success');
        }
    }).catch(err => {
        console.error('Failed to copy: ', err);
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        if (window.uniWhisperApp) {
            window.uniWhisperApp.showToast('Copied to clipboard (fallback)!', 'success');
        }
    });
}

// Initialize navigation when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Wait a bit for the main app to initialize and make uniWhisperApp available
    // The app.js now sets window.uniWhisperApp directly on DOMContentLoaded
    // So, a small timeout is still good practice to ensure it's fully constructed.
    setTimeout(() => {
        setupNavigation();
        // Initial load of notification badge count
        if (window.uniWhisperApp && window.uniWhisperApp.fetchNotifications) { // Check if method exists
            // Note: fetchNotifications in app.js is not designed to update a badge directly.
            // You'd need to add logic in app.js's fetchNotifications to update the badge.
            // For now, this call will just fetch notifications.
            // window.uniWhisperApp.fetchNotifications(); 
        }
    }, 100); // Reduced timeout as app.js now sets it immediately
});
