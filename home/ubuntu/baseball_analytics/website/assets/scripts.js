// JavaScript for Baseball Analytics Dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Filter functionality
    const applyFilterBtn = document.querySelector('.filter-bar button');
    if (applyFilterBtn) {
        applyFilterBtn.addEventListener('click', function() {
            // In a real application, this would filter the data
            // For demo purposes, just show an alert
            alert('Filters applied! In a real application, this would update the dashboard data.');
        });
    }
    
    // Export functionality
    const exportBtn = document.querySelector('button.btn-outline-secondary');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // In a real application, this would export the data
            // For demo purposes, just show an alert
            alert('Export initiated! In a real application, this would download dashboard data.');
        });
    }
    
    // New Analysis button
    const newAnalysisBtn = document.querySelector('button.btn-primary');
    if (newAnalysisBtn && newAnalysisBtn.textContent.includes('New Analysis')) {
        newAnalysisBtn.addEventListener('click', function() {
            // In a real application, this would open a new analysis form
            // For demo purposes, just show an alert
            alert('New analysis form would open here in the real application.');
        });
    }
    
    // Sidebar navigation
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // In a real application, this would navigate to different sections
            // For demo purposes, prevent default and show an alert if it's not the active link
            if (!this.classList.contains('active')) {
                e.preventDefault();
                
                // Remove active class from all links
                sidebarLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Show alert with the section name
                const sectionName = this.querySelector('span').textContent;
                alert(`Navigating to ${sectionName} section. This would load different content in the real application.`);
            }
        });
    });
    
    // Toggle between batting and pitching data
    const dataToggleBtns = document.querySelectorAll('.btn-group-sm .btn');
    dataToggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            dataToggleBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // In a real application, this would update the chart data
            // For demo purposes, just show an alert
            alert(`Switched to ${this.textContent.trim()} data. This would update the chart in the real application.`);
        });
    });
    
    // View All button for player rankings
    const viewAllBtn = document.querySelector('.card-header button.btn-outline-secondary');
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', function() {
            // In a real application, this would show all players
            // For demo purposes, just show an alert
            alert('This would show all players in the real application.');
        });
    }
    
    // Player card click event
    const playerCards = document.querySelectorAll('.player-card');
    playerCards.forEach(card => {
        card.addEventListener('click', function() {
            // In a real application, this would open player details
            // For demo purposes, just show an alert
            const playerName = this.querySelector('.player-name').textContent;
            alert(`This would open detailed stats for ${playerName} in the real application.`);
        });
    });
    
    // Recent activity click event
    const activityItems = document.querySelectorAll('.list-group-item');
    activityItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            // In a real application, this would open activity details
            // For demo purposes, just show an alert
            const activityTitle = this.querySelector('h6').textContent;
            alert(`This would show details for "${activityTitle}" in the real application.`);
        });
    });
});
