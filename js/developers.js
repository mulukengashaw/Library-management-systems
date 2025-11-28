// Developers page functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('devSearch');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const devCards = document.querySelectorAll('.dev-profile-card');
    const noResults = document.getElementById('noResults');
    const devGrid = document.getElementById('devGrid');

    // Search functionality
    searchInput.addEventListener('input', filterDevelopers);
    
    // Filter functionality
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            filterDevelopers();
        });
    });

    function filterDevelopers() {
        const searchTerm = searchInput.value.toLowerCase();
        const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
        
        let visibleCount = 0;

        devCards.forEach(card => {
            const name = card.querySelector('.dev-name').textContent.toLowerCase();
            const role = card.querySelector('.dev-role').textContent.toLowerCase();
            const skills = Array.from(card.querySelectorAll('.skill-tag'))
                .map(skill => skill.textContent.toLowerCase())
                .join(' ');
            
            const matchesSearch = name.includes(searchTerm) || 
                                role.includes(searchTerm) || 
                                skills.includes(searchTerm);
            
            const matchesFilter = activeFilter === 'all' || 
                                card.dataset.category === activeFilter;

            if (matchesSearch && matchesFilter) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0) {
            noResults.style.display = 'block';
            devGrid.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            devGrid.style.display = 'grid';
        }
    }

    // Action button functionality
    document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function() {
            const card = this.closest('.dev-profile-card');
            const name = card.querySelector('.dev-name').textContent;
            const action = this.title;

            if (action === 'Send Message') {
                alert(`Opening email composer for ${name}`);
                // In a real app, this would open an email client
            } else if (action === 'View Profile') {
                alert(`Opening full profile for ${name}`);
                // In a real app, this would navigate to a detailed profile page
            }
        });
    });

    // Social link functionality
    document.querySelectorAll('.social-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const platform = this.querySelector('i').className.split(' ')[1];
            alert(`Redirecting to ${platform} profile`);
            // In a real app, this would open the actual social media profile
        });
    });
});