/**
 * SEUP Notification Bell - Žuto kričavo zvono
 * (c) 2025 8Core Association
 * VERSION: 2.0.0-DEBUG
 */

console.log('🚀 notification-bell.js VERSION 2.0.0-DEBUG loaded!');

(function() {
    'use strict';

    let notificationsData = [];

    function getAjaxUrl(action) {
        const baseUrl = window.location.origin + window.location.pathname.split('/custom/')[0];
        return baseUrl + '/custom/seup/class/obavjesti_ajax.php?action=' + action;
    }

    function initNotificationBell() {
        const bell = document.getElementById('seupNotificationBell');
        const badge = document.getElementById('notificationCount');

        if (!bell || !badge) {
            console.warn('Notification bell elements not found');
            return;
        }

        bell.addEventListener('click', function() {
            handleBellClick();
        });

        loadNotifications();
    }

    function updateNotificationCount(count) {
        const badge = document.getElementById('notificationCount');
        const bell = document.getElementById('seupNotificationBell');

        if (!badge || !bell) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
            badge.setAttribute('data-count', count);
            bell.classList.add('has-notifications');
            bell.style.display = 'flex';
        } else {
            badge.textContent = '0';
            badge.style.display = 'none';
            badge.setAttribute('data-count', '0');
            bell.classList.remove('has-notifications');
            bell.style.display = 'none';
        }
    }

    function handleBellClick() {
        console.log('🔔 Bell clicked!');

        const bellIcon = document.querySelector('.bell-icon');
        if (bellIcon) {
            bellIcon.style.animation = 'none';
            setTimeout(() => {
                bellIcon.style.animation = 'bellRing 0.5s ease-in-out';
            }, 10);
        }

        showNotificationModal();
    }

    function showNotificationModal() {
        console.log('🚀 showNotificationModal() called');

        let existingModal = document.getElementById('seupNotificationModal');
        if (existingModal) {
            console.log('🗑️ Removing existing modal');
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'seupNotificationModal';
        modal.className = 'seup-notification-modal';
        console.log('📦 Modal element created:', modal);
        modal.innerHTML = `
            <div class="seup-notification-modal-overlay"></div>
            <div class="seup-notification-modal-content">
                <div class="seup-notification-modal-header">
                    <h3><i class="fas fa-bell"></i> Obavjesti</h3>
                    <button type="button" class="seup-notification-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="seup-notification-modal-body" id="notificationsList">
                    ${renderNotifications()}
                </div>
                <div class="seup-notification-modal-footer">
                    <button type="button" class="seup-btn seup-btn-sm seup-btn-secondary" id="markAllRead">
                        <i class="fas fa-check-double"></i> Označi sve pročitanim
                    </button>
                    <button type="button" class="seup-btn seup-btn-sm seup-btn-danger" id="deleteAll">
                        <i class="fas fa-trash-alt"></i> Obriši sve
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        setTimeout(() => modal.classList.add('show'), 10);

        const closeBtn = modal.querySelector('.seup-notification-close');
        const overlay = modal.querySelector('.seup-notification-modal-overlay');
        const markAllBtn = modal.querySelector('#markAllRead');
        const deleteAllBtn = modal.querySelector('#deleteAll');

        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeModal();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeModal();
            });
        }

        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                markAllAsRead();
            });
        }

        if (deleteAllBtn) {
            deleteAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                deleteAllNotifications();
            });
        }

        const markReadButtons = modal.querySelectorAll('.mark-read-btn');
        markReadButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                markAsRead(id);
            });
        });

        const deleteButtons = modal.querySelectorAll('.delete-btn');
        deleteButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                deleteNotification(id);
            });
        });
    }

    function renderNotifications() {
        if (notificationsData.length === 0) {
            return `
                <div class="seup-notification-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Nemate novih obavjesti</p>
                </div>
            `;
        }

        let html = '<div class="seup-notifications-list">';

        notificationsData.forEach(notification => {
            const subjectIcon = getSubjectIcon(notification.subjekt);
            const subjectClass = notification.subjekt;

            html += `
                <div class="seup-notification-item ${subjectClass}" data-id="${notification.id}">
                    <div class="seup-notification-item-header">
                        <span class="seup-notification-subject">${subjectIcon} ${notification.subjekt}</span>
                        <span class="seup-notification-date">${notification.datum}</span>
                    </div>
                    <h4 class="seup-notification-title">${escapeHtml(notification.naslov)}</h4>
                    <p class="seup-notification-content">${escapeHtml(notification.sadrzaj)}</p>
                    ${notification.vanjski_link ? `
                        <div class="seup-notification-link">
                            <a href="${notification.vanjski_link}" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-external-link-alt"></i> Više informacija
                            </a>
                        </div>
                    ` : ''}
                    <div class="seup-notification-actions">
                        <button type="button" class="seup-btn seup-btn-xs seup-btn-outline-primary mark-read-btn" data-id="${notification.id}">
                            <i class="fas fa-check"></i> Označi pročitano
                        </button>
                        <button type="button" class="seup-btn seup-btn-xs seup-btn-outline-danger delete-btn" data-id="${notification.id}">
                            <i class="fas fa-trash"></i> Obriši
                        </button>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        return html;
    }

    function getSubjectIcon(subjekt) {
        const icons = {
            'info': 'ℹ️',
            'upozorenje': '⚠️',
            'nadogradnja': '🔄',
            'hitno': '🚨',
            'vazno': '⭐'
        };
        return icons[subjekt] || 'ℹ️';
    }

    function closeModal() {
        const modal = document.getElementById('seupNotificationModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }

    function loadNotifications() {
        const ajaxUrl = getAjaxUrl('get_notifications');
        fetch(ajaxUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notificationsData = data.notifications;
                    updateNotificationCount(data.count);
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
            });
    }

    function markAsRead(id) {
        const ajaxUrl = getAjaxUrl('mark_read') + '&id=' + id;
        fetch(ajaxUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.seup-notification-item[data-id="${id}"]`);
                    if (item) {
                        item.classList.add('read');

                        const markBtn = item.querySelector('.mark-read-btn');
                        if (markBtn) {
                            markBtn.innerHTML = '<i class="fas fa-check-circle"></i> Pročitano';
                            markBtn.classList.remove('seup-btn-outline-primary');
                            markBtn.classList.add('seup-btn-outline-success');
                            markBtn.disabled = true;
                            markBtn.style.opacity = '0.6';
                            markBtn.style.cursor = 'not-allowed';
                        }

                        loadNotifications();
                    }
                }
            })
            .catch(error => {
                console.error('Error marking as read:', error);
            });
    }

    function markAllAsRead() {
        const ajaxUrl = getAjaxUrl('mark_all_read');
        fetch(ajaxUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadNotifications();
                }
            })
            .catch(error => {
                console.error('Error marking all as read:', error);
            });
    }

    function deleteNotification(id) {
        const ajaxUrl = getAjaxUrl('delete') + '&id=' + id;
        fetch(ajaxUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.seup-notification-item[data-id="${id}"]`);
                    if (item) {
                        item.style.transition = 'opacity 0.3s ease';
                        item.style.opacity = '0';
                        setTimeout(() => {
                            item.remove();
                            loadNotifications();

                            const listContainer = document.getElementById('notificationsList');
                            const remainingItems = listContainer.querySelectorAll('.seup-notification-item');
                            if (remainingItems.length === 0) {
                                listContainer.innerHTML = renderNotifications();
                            }
                        }, 300);
                    }
                }
            })
            .catch(error => {
                console.error('Error deleting notification:', error);
            });
    }

    function deleteAllNotifications() {
        const ajaxUrl = getAjaxUrl('delete_all');
        fetch(ajaxUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadNotifications();
                }
            })
            .catch(error => {
                console.error('Error deleting all notifications:', error);
            });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function startAutoRefresh() {
        setInterval(function() {
            loadNotifications();
        }, 30000);
    }

    if (document.readyState === 'loading') {
        console.log('⏳ Document loading, waiting for DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ DOMContentLoaded fired, initializing bell...');
            initNotificationBell();
            startAutoRefresh();
        });
    } else {
        console.log('✅ Document already loaded, initializing bell...');
        initNotificationBell();
        startAutoRefresh();
    }

})();
