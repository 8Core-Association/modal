/**
 * SEUP Notification Bell - Žuto kričavo zvono
 * (c) 2025 8Core Association
 */

(function() {
    'use strict';

    let notificationsData = [];

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
        } else {
            badge.textContent = '0';
            badge.style.display = 'none';
            badge.setAttribute('data-count', '0');
            bell.classList.remove('has-notifications');
        }
    }

    function handleBellClick() {
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
        let existingModal = document.getElementById('seupNotificationModal');
        if (existingModal) {
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'seupNotificationModal';
        modal.className = 'seup-notification-modal';
        modal.innerHTML = `
            <div class="seup-notification-modal-overlay"></div>
            <div class="seup-notification-modal-content">
                <div class="seup-notification-modal-header">
                    <h3><i class="fas fa-bell"></i> Obavjesti</h3>
                    <button class="seup-notification-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="seup-notification-modal-body" id="notificationsList">
                    ${renderNotifications()}
                </div>
                <div class="seup-notification-modal-footer">
                    <button class="seup-btn seup-btn-sm seup-btn-secondary" id="markAllRead">
                        <i class="fas fa-check-double"></i> Označi sve pročitanim
                    </button>
                    <button class="seup-btn seup-btn-sm seup-btn-danger" id="deleteAll">
                        <i class="fas fa-trash-alt"></i> Obriši sve
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        setTimeout(() => modal.classList.add('show'), 10);

        modal.querySelector('.seup-notification-close').addEventListener('click', closeModal);
        modal.querySelector('.seup-notification-modal-overlay').addEventListener('click', closeModal);
        modal.querySelector('#markAllRead').addEventListener('click', markAllAsRead);
        modal.querySelector('#deleteAll').addEventListener('click', deleteAllNotifications);

        attachNotificationActions();
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
                            <a href="${escapeHtml(notification.vanjski_link)}" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Više informacija
                            </a>
                        </div>
                    ` : ''}
                    <div class="seup-notification-actions">
                        <button class="seup-btn seup-btn-xs seup-btn-outline-primary mark-read-btn" data-id="${notification.id}">
                            <i class="fas fa-check"></i> Označi pročitano
                        </button>
                        <button class="seup-btn seup-btn-xs seup-btn-outline-danger delete-btn" data-id="${notification.id}">
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

    function attachNotificationActions() {
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                markAsRead(id);
            });
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                deleteNotification(id);
            });
        });
    }

    function closeModal() {
        const modal = document.getElementById('seupNotificationModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }

    function loadNotifications() {
        fetch('/custom/seup/class/obavjesti_ajax.php?action=get_notifications')
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
        fetch('/custom/seup/class/obavjesti_ajax.php?action=mark_read&id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.seup-notification-item[data-id="${id}"]`);
                    if (item) {
                        item.style.opacity = '0.5';
                        setTimeout(() => {
                            item.remove();
                            loadNotifications();

                            const listContainer = document.getElementById('notificationsList');
                            if (listContainer && notificationsData.length === 1) {
                                listContainer.innerHTML = renderNotifications();
                            }
                        }, 300);
                    }
                }
            })
            .catch(error => {
                console.error('Error marking as read:', error);
            });
    }

    function markAllAsRead() {
        if (!confirm('Označiti sve obavjesti kao pročitane?')) return;

        fetch('/custom/seup/class/obavjesti_ajax.php?action=mark_all_read')
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
        if (!confirm('Obrisati ovu obavjest?')) return;

        fetch('/custom/seup/class/obavjesti_ajax.php?action=delete&id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.seup-notification-item[data-id="${id}"]`);
                    if (item) {
                        item.style.opacity = '0.5';
                        setTimeout(() => {
                            item.remove();
                            loadNotifications();

                            const listContainer = document.getElementById('notificationsList');
                            if (listContainer && notificationsData.length === 1) {
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
        if (!confirm('Obrisati SVE obavjesti? Ova akcija se ne može poništiti.')) return;

        fetch('/custom/seup/class/obavjesti_ajax.php?action=delete_all')
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
        document.addEventListener('DOMContentLoaded', function() {
            initNotificationBell();
            startAutoRefresh();
        });
    } else {
        initNotificationBell();
        startAutoRefresh();
    }

})();
