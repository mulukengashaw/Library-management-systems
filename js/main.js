// // Main JavaScript functionality

// document.addEventListener('DOMContentLoaded', function() {
//     // Phase tracker functionality
//     initializePhaseTracker();
    
//     // Search functionality
//     initializeSearch();
    
//     // Modal functionality
//     initializeModals();
// });

// // Phase Tracker
// function initializePhaseTracker() {
//     const expandToggles = document.querySelectorAll('.expand-toggle');
    
//     expandToggles.forEach(toggle => {
//         toggle.addEventListener('click', function() {
//             const phaseCard = this.closest('.phase-card');
//             phaseCard.classList.toggle('expanded');
            
//             // Update toggle icon
//             const icon = this.querySelector('svg');
//             if (phaseCard.classList.contains('expanded')) {
//                 icon.innerHTML = '<path d="m6 15 6-6 6 6"></path>';
//             } else {
//                 icon.innerHTML = '<path d="m6 9 6 6 6-6"></path>';
//             }
//         });
//     });
    
//     // AI Consultation Form
//     const aiForm = document.getElementById('ai-consultation-form');
//     if (aiForm) {
//         aiForm.addEventListener('submit', function(e) {
//             e.preventDefault();
//             handleAIConsultation();
//         });
//     }
// }

// function handleAIConsultation() {
//     const queryInput = document.getElementById('ai-query');
//     const responseContainer = document.getElementById('ai-response');
//     const responseContent = responseContainer.querySelector('.response-content');
//     const submitBtn = document.querySelector('.ai-submit-btn');
    
//     const query = queryInput.value.trim();
//     if (!query) return;
    
//     // Show loading state
//     submitBtn.disabled = true;
//     submitBtn.innerHTML = 'Thinking...';
    
//     // Simulate API call (replace with actual API call)
//     setTimeout(() => {
//         // Mock response
//         const responses = [
//             "For wireframing in the Design phase, I recommend using Figma for its collaborative features and component library. It integrates well with development workflows and has excellent prototyping capabilities.",
//             "For database schema design, consider using PostgreSQL with proper normalization. Start with users, books, loans, and fines tables. Implement indexes on frequently searched fields like title and author.",
//             "For the frontend development, React with TypeScript provides type safety and better developer experience. Use Tailwind CSS for rapid UI development and consistent design system.",
//             "For authentication, implement JWT tokens with secure HTTP-only cookies. Consider using OAuth2 for social login options to improve user onboarding experience."
//         ];
        
//         const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        
//         responseContent.textContent = randomResponse;
//         responseContainer.style.display = 'block';
        
//         // Reset form
//         submitBtn.disabled = false;
//         submitBtn.innerHTML = 'Ask AI Architect <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>';
//     }, 2000);
// }

// // Search functionality
// function initializeSearch() {
//     const searchInput = document.getElementById('search-input');
//     if (searchInput) {
//         searchInput.addEventListener('input', function() {
//             const searchTerm = this.value.toLowerCase();
//             const bookCards = document.querySelectorAll('.catalog-card');
            
//             bookCards.forEach(card => {
//                 const title = card.querySelector('h3').textContent.toLowerCase();
//                 const author = card.querySelector('p').textContent.toLowerCase();
                
//                 if (title.includes(searchTerm) || author.includes(searchTerm)) {
//                     card.style.display = 'block';
//                 } else {
//                     card.style.display = 'none';
//                 }
//             });
//         });
//     }
// }

// // Modal functionality
// function initializeModals() {
//     // Book detail modal
//     const bookCards = document.querySelectorAll('.catalog-card');
//     const bookModal = document.getElementById('book-modal');
//     const modalClose = document.querySelector('.modal-close');
    
//     if (bookModal && modalClose) {
//         // Open modal
//         bookCards.forEach(card => {
//             card.addEventListener('click', function() {
//                 const bookId = this.getAttribute('data-book-id');
//                 openBookModal(bookId);
//             });
//         });
        
//         // Close modal
//         modalClose.addEventListener('click', closeModal);
//         bookModal.addEventListener('click', function(e) {
//             if (e.target === this || e.target.classList.contains('modal-overlay')) {
//                 closeModal();
//             }
//         });
        
//         // Close on Escape key
//         document.addEventListener('keydown', function(e) {
//             if (e.key === 'Escape' && bookModal.style.display !== 'none') {
//                 closeModal();
//             }
//         });
//     }
// }

// function openBookModal(bookId) {
//     const modal = document.getElementById('book-modal');
//     const books = {
//         '1': {
//             title: 'The Great Gatsby',
//             author: 'F. Scott Fitzgerald',
//             category: 'Fiction',
//             status: 'Available',
//             isbn: '9780743273565',
//             coverUrl: 'https://picsum.photos/seed/gatsby/300/450'
//         },
//         '2': {
//             title: 'Clean Code',
//             author: 'Robert C. Martin',
//             category: 'Technology',
//             status: 'Borrowed',
//             isbn: '9780132350884',
//             coverUrl: 'https://picsum.photos/seed/clean/300/450'
//         },
//         '3': {
//             title: 'Sapiens',
//             author: 'Yuval Noah Harari',
//             category: 'History',
//             status: 'Available',
//             isbn: '9780062316097',
//             coverUrl: 'https://picsum.photos/seed/sapiens/300/450'
//         },
//         '4': {
//             title: 'Dune',
//             author: 'Frank Herbert',
//             category: 'Sci-Fi',
//             status: 'Hold',
//             isbn: '9780441013593',
//             coverUrl: 'https://picsum.photos/seed/dune/300/450'
//         },
//         '5': {
//             title: 'Atomic Habits',
//             author: 'James Clear',
//             category: 'Self-Help',
//             status: 'Available',
//             isbn: '9780735211292',
//             coverUrl: 'https://picsum.photos/seed/atomic/300/450'
//         },
//         '6': {
//             title: 'Project Hail Mary',
//             author: 'Andy Weir',
//             category: 'Sci-Fi',
//             status: 'Available',
//             isbn: '9780593135204',
//             coverUrl: 'https://picsum.photos/seed/weir/300/450'
//         }
//     };
    
//     const book = books[bookId];
//     if (!book) return;
    
//     // Populate modal with book data
//     document.getElementById('modal-cover').src = book.coverUrl;
//     document.getElementById('modal-category').textContent = book.category;
//     document.getElementById('modal-title').textContent = book.title;
//     document.getElementById('modal-author').textContent = `by ${book.author}`;
//     document.getElementById('modal-isbn').textContent = book.isbn;
    
//     const statusElement = document.getElementById('modal-status');
//     statusElement.textContent = book.status;
//     statusElement.className = `detail-value status-badge ${getStatusClass(book.status)}`;
    
//     // Update borrow button text based on status
//     const borrowBtn = document.querySelector('.borrow-btn');
//     if (book.status === 'Available') {
//         borrowBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg> Borrow Now';
//     } else {
//         borrowBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"></path></svg> Place Hold';
//     }
    
//     // Generate AI insights
//     generateBookInsights(book.title, book.author);
    
//     // Show modal
//     modal.style.display = 'block';
//     document.body.style.overflow = 'hidden';
// }

// function closeModal() {
//     const modal = document.getElementById('book-modal');
//     modal.style.display = 'none';
//     document.body.style.overflow = 'auto';
// }

// function getStatusClass(status) {
//     const statusClasses = {
//         'Available': 'status-available',
//         'Borrowed': 'status-borrowed',
//         'Hold': 'status-hold'
//     };
//     return statusClasses[status] || '';
// }

// function generateBookInsights(title, author) {
//     const insightContent = document.getElementById('ai-insight-content');
    
//     // Show loading state
//     insightContent.innerHTML = `
//         <div class="loading-pulse">
//             <div class="pulse-line"></div>
//             <div class="pulse-line"></div>
//         </div>
//     `;
    
//     // Simulate API call to generate insights
//     setTimeout(() => {
//         const insights = {
//             'The Great Gatsby': 'Summary: A classic American novel exploring the decadence and idealism of the Jazz Age through the eyes of narrator Nick Carraway. Recommendation: Try "Tender Is the Night" by the same author.',
//             'Clean Code': 'Summary: Essential reading for software developers, offering practical advice on writing maintainable, efficient code. Recommendation: "The Pragmatic Programmer" for complementary insights.',
//             'Sapiens': 'Summary: A sweeping history of humankind that explores how biology and history have defined us. Recommendation: "Homo Deus" continues the exploration of humanity\'s future.',
//             'Dune': 'Summary: Epic science fiction masterpiece set in a distant future amidst a feudal interstellar society. Recommendation: "Foundation" by Isaac Asimov for similar scope.',
//             'Atomic Habits': 'Summary: Practical guide to building good habits and breaking bad ones through tiny changes. Recommendation: "The Power of Habit" for deeper scientific background.',
//             'Project Hail Mary': 'Summary: A lone astronaut must save the earth from disaster in this gripping science fiction adventure. Recommendation: "The Martian" by the same author.'
//         };
        
//         const insight = insights[title] || `Summary: "${title}" by ${author} is a notable work in its genre. Recommendation: Explore similar titles in the ${document.getElementById('modal-category').textContent} section.`;
        
//         insightContent.innerHTML = `<p>"${insight}"</p>`;
//     }, 1500);
// }