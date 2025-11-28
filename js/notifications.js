// Notification System
class NotificationSystem {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create notification container if it doesn't exist
        if (!document.getElementById('notification-container')) {
            this.container = document.createElement('div');
            this.container.id = 'notification-container';
            this.container.className = 'notification-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('notification-container');
        }
    }

    show(options) {
        const {
            title,
            message,
            type = 'info',
            duration = 5000,
            action = null
        } = options;

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icon = this.getIcon(type);
        
        notification.innerHTML = `
            <div class="notification-icon">
                ${icon}
            </div>
            <div class="notification-content">
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
                ${action ? `
                <div class="notification-actions" style="margin-top: 8px;">
                    <button class="btn btn-sm btn-outline" onclick="${action.handler}">
                        ${action.text}
                    </button>
                </div>
                ` : ''}
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        `;

        // Add to container
        this.container.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                this.hide(notification);
            }, duration);
        }

        return notification;
    }

    hide(notification) {
        notification.classList.remove('show');
        notification.classList.add('hide');
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }

    getIcon(type) {
        const icons = {
            success: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>`,
            error: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
            warning: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
            info: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`
        };
        return icons[type] || icons.info;
    }

    // Quick notification methods
    success(message, title = 'Success!', duration = 5000) {
        return this.show({ title, message, type: 'success', duration });
    }

    error(message, title = 'Error!', duration = 7000) {
        return this.show({ title, message, type: 'error', duration });
    }

    warning(message, title = 'Warning!', duration = 6000) {
        return this.show({ title, message, type: 'warning', duration });
    }

    info(message, title = 'Information', duration = 4000) {
        return this.show({ title, message, type: 'info', duration });
    }
}

// Proof System
class ProofSystem {
    static createBadge(text, type = 'success') {
        const badge = document.createElement('span');
        badge.className = `proof-badge ${type}`;
        badge.innerHTML = `
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            ${text}
        `;
        return badge;
    }

    static createActionProof(title, content, time = null) {
        const proof = document.createElement('div');
        proof.className = 'action-proof';
        
        const timestamp = time || new Date().toLocaleTimeString();
        
        proof.innerHTML = `
            <div class="action-proof-header">
                <div class="action-proof-title">${title}</div>
                <div class="action-proof-time">${timestamp}</div>
            </div>
            <div class="action-proof-content">${content}</div>
        `;
        
        return proof;
    }

    static showTransactionProof(transaction) {
        const notifications = new NotificationSystem();
        
        let message = '';
        let type = 'info';
        
        switch(transaction.type) {
            case 'borrow':
                message = `"${transaction.book}" borrowed successfully. Due: ${transaction.dueDate}`;
                type = 'success';
                break;
            case 'return':
                message = `"${transaction.book}" returned successfully.`;
                type = 'success';
                break;
            case 'reserve':
                message = `"${transaction.book}" reserved. You'll be notified when available.`;
                type = 'info';
                break;
            case 'fine':
                message = `Fine applied: $${transaction.amount} for "${transaction.book}"`;
                type = 'warning';
                break;
        }
        
        notifications.show({
            title: 'Transaction Completed',
            message: message,
            type: type,
            duration: 6000,
            action: {
                text: 'View Details',
                handler: `showTransactionDetails('${transaction.id}')`
            }
        });
    }
}

// Floating Action Button System
class FABSystem {
    constructor() {
        this.fab = null;
        this.quickActions = null;
        this.isOpen = false;
        this.init();
    }

    init() {
        // Create FAB if it doesn't exist
        if (!document.getElementById('main-fab')) {
            this.fab = document.createElement('button');
            this.fab.id = 'main-fab';
            this.fab.className = 'fab fab-pulse';
            this.fab.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            `;
            this.fab.addEventListener('click', () => this.toggleQuickActions());
            document.body.appendChild(this.fab);
        } else {
            this.fab = document.getElementById('main-fab');
        }

        this.createQuickActions();
    }

    createQuickActions() {
        this.quickActions = document.createElement('div');
        this.quickActions.className = 'quick-actions';
        
        const actions = [
            {
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>',
                title: 'Quick Borrow',
                handler: 'quickBorrow()',
                type: 'success'
            },
            {
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
                title: 'Generate Report',
                handler: 'generateReport()',
                type: 'info'
            },
            {
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
                title: 'System Status',
                handler: 'showSystemStatus()',
                type: 'warning'
            }
        ];

        actions.forEach((action, index) => {
            const quickAction = document.createElement('button');
            quickAction.className = `quick-action ${action.type}`;
            quickAction.innerHTML = action.icon;
            quickAction.title = action.title;
            quickAction.addEventListener('click', () => {
                eval(action.handler);
                this.hideQuickActions();
            });
            
            this.quickActions.appendChild(quickAction);
        });

        document.body.appendChild(this.quickActions);
    }

    toggleQuickActions() {
        if (this.isOpen) {
            this.hideQuickActions();
        } else {
            this.showQuickActions();
        }
    }

    showQuickActions() {
        const actions = this.quickActions.querySelectorAll('.quick-action');
        actions.forEach((action, index) => {
            setTimeout(() => {
                action.classList.add('show');
            }, index * 100);
        });
        this.isOpen = true;
        
        // Change FAB icon
        this.fab.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        `;
    }

    hideQuickActions() {
        const actions = this.quickActions.querySelectorAll('.quick-action');
        actions.forEach(action => {
            action.classList.remove('show');
        });
        this.isOpen = false;
        
        // Change FAB icon back
        this.fab.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
        `;
    }
}

// Initialize systems when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.notifications = new NotificationSystem();
    window.fabSystem = new FABSystem();
});

// Quick action functions
function quickBorrow() {
    notifications.success('Quick borrow initiated!', 'Scan book ISBN to continue...');
    // Simulate scanning process
    setTimeout(() => {
        ProofSystem.showTransactionProof({
            type: 'borrow',
            book: 'The Great Gatsby',
            dueDate: 'Dec 15, 2024',
            id: 'TRX-' + Date.now()
        });
    }, 2000);
}

function generateReport() {
    notifications.info('Generating monthly report...', 'Report System');
    
    // Simulate report generation
    setTimeout(() => {
        notifications.success('Monthly report generated successfully!', 'Report Ready');
        
        // Show proof of report generation
        const proof = ProofSystem.createActionProof(
            'Monthly Report Generated',
            'Library circulation report for November 2024 has been generated and is ready for download.',
            new Date().toLocaleTimeString()
        );
        
        // Add to a proof container if it exists
        const proofContainer = document.getElementById('proof-container');
        if (proofContainer) {
            proofContainer.insertBefore(proof, proofContainer.firstChild);
        }
    }, 3000);
}

function showSystemStatus() {
    const status = {
        database: 'Connected',
        api: 'Online',
        storage: '85% used',
        users: '1,243 active'
    };
    
    let message = '';
    for (const [key, value] of Object.entries(status)) {
        message += `${key}: ${value}\n`;
    }
    
    notifications.show({
        title: 'System Status',
        message: message.trim(),
        type: 'info',
        duration: 8000
    });
}

function showTransactionDetails(transactionId) {
    notifications.info(`Loading transaction details for: ${transactionId}`, 'Transaction Viewer');
    // In a real app, this would open a modal or navigate to transaction details
}