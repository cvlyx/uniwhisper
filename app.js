class UniWhisper {
    constructor() {
        // API base URL - adjust for your server
        this.apiBase = 'http://localhost/uniwhisper'; // For Apache
        // this.apiBase = 'http://localhost:8000'; // For php -S localhost:8000

        // User management
        this.anonId = null;
        this.currentCommentPostId = null;
        this.currentView = 'feed'; // feed, explore, notifications, profile
        this.notifications = [];
        this.profileData = null;
        this.exploreData = null;

        // Initialize the application
        this.init();
    }

    async init() {
        console.log('Initializing UniWhisper...');
        console.log('Checking initial DOM for feed-section:', !!document.getElementById('feed-section'));

        // Setup event listeners
        this.setupEventListeners();

        // Initialize user
        await this.initializeUser();

        // Ensure DOM is fully loaded
        await new Promise(resolve => {
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                resolve();
            } else {
                document.addEventListener('DOMContentLoaded', resolve, { once: true });
            }
        });

        // Load initial view
        await this.loadView('feed');

        console.log('UniWhisper initialized successfully');
    }

    setupEventListeners() {
        // Post form
        const postForm = document.getElementById('post-form');
        const postContent = document.getElementById('post-content');
        const submitBtn = document.getElementById('submit-btn');
        const charCount = document.getElementById('char-count');
        const postMedia = document.getElementById('post-media');

        if (postForm && postContent && submitBtn && charCount) {
            postContent.addEventListener('input', () => {
                const length = postContent.value.length;
                charCount.textContent = `${length}/1000 characters`;
                submitBtn.disabled = length === 0 || length > 1000;
            });

            postForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitPost();
            });
        }

        // Comment modal
        const commentModal = document.getElementById('comment-modal');
        const commentContent = document.getElementById('comment-content');
        const commentCharCount = document.getElementById('comment-char-count');
        const submitCommentBtn = document.getElementById('submit-comment');
        const cancelCommentBtn = document.getElementById('cancel-comment');
        const commentMedia = document.getElementById('comment-media');

        if (commentModal && commentContent && commentCharCount && submitCommentBtn && cancelCommentBtn) {
            commentContent.addEventListener('input', () => {
                const length = commentContent.value.length;
                commentCharCount.textContent = `${length}/500 characters`;
                submitCommentBtn.disabled = length === 0 || length > 500;
            });

            submitCommentBtn.addEventListener('click', () => {
                this.submitComment();
            });

            cancelCommentBtn.addEventListener('click', () => {
                this.closeCommentModal();
            });

            commentModal.addEventListener('click', (e) => {
                if (e.target === commentModal) {
                    this.closeCommentModal();
                }
            });
        }

        // Navigation
        const navFeed = document.getElementById('nav-feed');
        const navExplore = document.getElementById('nav-explore');
        const navNotifications = document.getElementById('nav-notifications');
        const navProfile = document.getElementById('nav-profile');

        if (navFeed) navFeed.addEventListener('click', () => this.loadView('feed'));
        if (navExplore) navExplore.addEventListener('click', () => this.loadView('explore'));
        if (navNotifications) navNotifications.addEventListener('click', () => this.loadView('notifications'));
        if (navProfile) navProfile.addEventListener('click', () => this.loadView('profile'));

        // Profile actions
        const editProfileBtn = document.getElementById('edit-profile-btn');
        const saveProfileBtn = document.getElementById('save-profile-btn');
        const cancelEditProfileBtn = document.getElementById('cancel-edit-profile-btn');
        const copyIdBtn = document.getElementById('copy-id-btn');

        if (editProfileBtn) editProfileBtn.addEventListener('click', () => this.toggleEditProfile(true));
        if (saveProfileBtn) saveProfileBtn.addEventListener('click', () => this.saveProfile());
        if (cancelEditProfileBtn) cancelEditProfileBtn.addEventListener('click', () => this.toggleEditProfile(false));
        if (copyIdBtn) copyIdBtn.addEventListener('click', () => this.copyAnonId());
    }

    async apiRequest(endpoint, options = {}) {
        try {
            console.log(`Making API request to ${endpoint}`);
            
            // Don't set Content-Type header for FormData - browser will set it automatically
            const headers = {};
            if (!(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
            }
            
            const response = await fetch(`${this.apiBase}/${endpoint}`, {
                ...options,
                headers: {
                    ...headers,
                    ...options.headers
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error ${response.status}: ${response.statusText} - ${errorText.substring(0, 100)}...`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Expected JSON, got ${contentType}: ${text.substring(0, 100)}...`);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'API request failed');
            }

            return data;
        } catch (error) {
            console.error(`Error in API request to ${endpoint}:`, error.message, error.stack);
            this.showToast(`Error: ${error.message}`, 'error');
            throw error;
        }
    }

    async initializeUser() {
        this.anonId = localStorage.getItem('uniwhisper_anon_id');

        if (this.anonId) {
            try {
                const data = await this.apiRequest(`check_user.php?anon_id=${encodeURIComponent(this.anonId)}`);
                if (!data.exists) {
                    await this.createNewUser();
                }
            } catch (error) {
                console.error('Error checking user:', error);
                await this.createNewUser();
            }
        } else {
            await this.createNewUser();
        }

        this.updateUserStatus();
    }

    async createNewUser() {
        try {
            const data = await this.apiRequest('register_user.php', {
                method: 'POST',
                body: JSON.stringify({
                    selected_name: 'Anonymous User',
                    selected_avatar: 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A'
                })
            });
            this.anonId = data.anon_id;
            localStorage.setItem('uniwhisper_anon_id', this.anonId);
            console.log('New anonymous user created:', this.anonId);
        } catch (error) {
            console.error('Error creating user:', error);
            this.showToast(`Error creating anonymous user: ${error.message}`, 'error');
        }
    }

    updateUserStatus() {
        const userStatus = document.getElementById('user-status');
        const userId = document.getElementById('user-id');
        if (userStatus && userId && this.anonId) {
            userStatus.innerHTML = `<i class="fas fa-user-secret mr-1 text-primary-600"></i> Anonymous User`;
            userId.innerHTML = `ID: ${this.anonId.substring(0, 12)}... <button id="copy-id-btn" class="ml-2 text-primary-500 hover:text-primary-700 text-xs"><i class="fas fa-copy"></i> Copy</button>`;
            document.getElementById('copy-id-btn')?.addEventListener('click', () => this.copyAnonId());
        }
    }

    async copyAnonId() {
        if (this.anonId) {
            try {
                await navigator.clipboard.writeText(this.anonId);
                this.showToast('User ID copied to clipboard!', 'success');
            } catch (error) {
                console.error('Error copying anon_id:', error);
                this.showToast('Failed to copy User ID', 'error');
            }
        }
    }

    async loadView(view, forceRefresh = false) {
        this.currentView = view;

        // Update navigation
        document.querySelectorAll('.nav-btn').forEach(item => {
            item.classList.remove('text-primary-600');
            item.classList.add('text-neutral-500');
        });
        const activeNav = document.getElementById(`nav-${view}`);
        if (activeNav) {
            activeNav.classList.remove('text-neutral-500');
            activeNav.classList.add('text-primary-600');
        }

        // Hide all sections
        ['feed-section', 'explore-section', 'notifications-section', 'profile-section'].forEach(id => {
            const section = document.getElementById(id);
            if (section) section.classList.add('hidden');
        });

        // Show active section
        const activeSection = document.getElementById(`${view}-section`);
        if (activeSection) {
            activeSection.classList.remove('hidden');
        } else {
            this.showToast(`Error: ${view} container not found`, 'error');
            return;
        }

        // Load view content
        try {
            if (view === 'feed') await this.loadFeed(forceRefresh);
            else if (view === 'explore') await this.loadExplore(forceRefresh);
            else if (view === 'notifications') await this.loadNotifications(forceRefresh);
            else if (view === 'profile') await this.loadProfile(forceRefresh);
        } catch (error) {
            console.error(`Error loading view ${view}:`, error);
            this.showToast(`Error loading ${view}: ${error.message}`, 'error');
        }
    }

    async loadFeed(forceRefresh = false) {
        console.log('Checking for feed-section:', !!document.getElementById('feed-section'));
        let feedSection = document.getElementById('feed-section-content');
        if (!feedSection) {
            console.warn('Feed content container not found, creating fallback');
            feedSection = document.createElement('div');
            feedSection.id = 'feed-section-content';
            feedSection.className = 'space-y-5';
            const mainSection = document.getElementById('feed-section');
            if (mainSection) {
                mainSection.appendChild(feedSection);
            } else {
                this.showToast('Error: Feed section not found', 'error');
                return;
            }
        }

        feedSection.innerHTML = `
            <div class="inline-flex items-center justify-center glass-effect rounded-2xl shadow px-6 py-4">
                <div class="animate-pulse-soft mr-3">
                    <div class="h-5 w-5 bg-primary-400 rounded-full"></div>
                </div>
                <p class="text-neutral-600 font-medium">Loading campus whispers...</p>
            </div>
        `;

        try {
            const data = await this.apiRequest(`fetch_posts.php?anon_id=${encodeURIComponent(this.anonId)}`);
            const posts = data.posts || [];

            if (posts.length === 0) {
                feedSection.innerHTML = `
                    <div class="glass-effect rounded-2xl shadow-lg p-8 max-w-md mx-auto elegant-card text-center" data-aos="zoom-in">
                        <div class="w-16 h-16 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-4 floating-element">
                            <i class="fas fa-comment-slash text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-neutral-700 mb-2">No whispers yet</h3>
                        <p class="text-neutral-500 mb-4">Be the first to share something on UniWhisper!</p>
                        <button id="create-first-post" class="gradient-bg text-white px-4 py-2 rounded-lg font-medium shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 animate-ripple">
                            <i class="fas fa-plus mr-2"></i> Create First Post
                        </button>
                    </div>
                `;
                document.getElementById('create-first-post')?.addEventListener('click', () => {
                    document.getElementById('post-content')?.focus();
                });
                AOS.refresh();
                return;
            }

            feedSection.innerHTML = posts.map(post => `
                <div class="post-card glass-effect rounded-2xl shadow-xl p-6 elegant-card relative" data-aos="fade-up">
                    <p class="text-neutral-800">${this.escapeHtml(post.content)}</p>
                    ${post.image ? `<img src="${post.image}" alt="Post image" class="mt-2 rounded-lg max-w-full h-auto">` : ''}
                    <div class="flex justify-between items-center mt-3">
                        <div class="flex items-center space-x-4">
                            <button class="like-btn" data-post-id="${post.id}">
                                <i class="fas fa-heart ${post.liked ? 'liked text-red-500' : 'text-neutral-500'}"></i> ${post.like_count || 0}
                            </button>
                            <button class="comment-btn" data-post-id="${post.id}">
                                <i class="fas fa-comment text-blue-500"></i> ${post.comment_count || 0}
                            </button>
                        </div>
                        <span class="text-xs text-neutral-500">${this.getTimeAgo(new Date(post.created_at))}</span>
                    </div>
                    <div class="comments mt-3" data-post-id="${post.id}">
                        ${post.comments ? post.comments.map(comment => `
                            <div class="text-sm text-neutral-600 border-t border-neutral-100/50 pt-2">
                                ${this.escapeHtml(comment.content)}
                                ${comment.media ? `<div class="mt-2"><img src="${comment.media}" alt="Comment media" class="rounded-lg max-w-full h-auto"></div>` : ''}
                                ${comment.tags ? `<div class="flex flex-wrap gap-2 mt-2">${JSON.parse(comment.tags).map(tag => `<span class="bg-primary-100 text-primary-600 px-2 py-1 rounded-full text-xs">${this.escapeHtml(tag)}</span>`).join('')}</div>` : ''}
                                <span class="text-xs text-neutral-500">(${this.getTimeAgo(new Date(comment.created_at))})</span>
                            </div>
                        `).join('') : ''}
                    </div>
                </div>
            `).join('');

            document.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', () => this.toggleLike(btn.dataset.postId));
            });
            document.querySelectorAll('.comment-btn').forEach(btn => {
                btn.addEventListener('click', () => this.openCommentModal(btn.dataset.postId));
            });

            AOS.refresh();
        } catch (error) {
            feedSection.innerHTML = `
                <div class="glass-effect rounded-2xl shadow-lg p-8 max-w-md mx-auto elegant-card text-center" data-aos="zoom-in">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-neutral-700 mb-2">Error loading posts</h3>
                    <p class="text-neutral-500 mb-4">Something went wrong. Please try again later.</p>
                    <button id="retry-feed" class="gradient-bg text-white px-4 py-2 rounded-lg font-medium shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 animate-ripple">
                        <i class="fas fa-redo mr-2"></i> Retry
                    </button>
                </div>
            `;
            document.getElementById('retry-feed')?.addEventListener('click', () => this.loadFeed(true));
            console.error('Error in loadFeed:', error.message, error.stack);
            AOS.refresh();
        }
    }

    async loadExplore(forceRefresh = false) {
        let exploreContent = document.getElementById('explore-content');
        if (!exploreContent) {
            console.warn('Explore content container not found, creating fallback');
            exploreContent = document.createElement('div');
            exploreContent.id = 'explore-content';
            exploreContent.className = 'space-y-8';
            const exploreSection = document.getElementById('explore-section');
            if (exploreSection) {
                exploreSection.appendChild(exploreContent);
            } else {
                this.showToast('Error: Explore section not found', 'error');
                return;
            }
        }

        exploreContent.innerHTML = `
            <div class="inline-flex items-center justify-center glass-effect rounded-2xl shadow px-6 py-4">
                <div class="animate-pulse-soft mr-3">
                    <div class="h-5 w-5 bg-primary-400 rounded-full"></div>
                </div>
                <p class="text-neutral-600 font-medium">Loading explore data...</p>
            </div>
        `;

        try {
            if (forceRefresh || !this.exploreData) {
                this.exploreData = await this.apiRequest(`fetch_explore.php?anon_id=${encodeURIComponent(this.anonId)}`);
            }

            const { trending_posts, popular_tags, active_users } = this.exploreData;

            exploreContent.innerHTML = `
                <div data-aos="fade-up">
                    <h3 class="text-xl font-semibold text-neutral-700 mb-4">Trending Posts</h3>
                    <div class="space-y-4">
                        ${trending_posts && trending_posts.length ? trending_posts.map(post => `
                            <div class="post-card glass-effect rounded-2xl shadow-xl p-6 elegant-card" data-aos="fade-up">
                                <p class="text-neutral-800">${this.escapeHtml(post.content)}</p>
                                ${post.image ? `<img src="${post.image}" alt="Post image" class="mt-2 rounded-lg max-w-full h-auto">` : ''}
                                <div class="flex justify-between items-center mt-3">
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm text-neutral-500"><i class="fas fa-heart text-red-500"></i> ${post.like_count || 0}</span>
                                        <span class="text-sm text-neutral-500"><i class="fas fa-comment text-blue-500"></i> ${post.comment_count || 0}</span>
                                    </div>
                                    <span class="text-xs text-neutral-500">${this.getTimeAgo(new Date(post.created_at))}</span>
                                </div>
                            </div>
                        `).join('') : '<p class="text-neutral-500">No trending posts yet.</p>'}
                    </div>
                </div>
                <div class="mt-6" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-xl font-semibold text-neutral-700 mb-4">Popular Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        ${popular_tags && popular_tags.length ? popular_tags.map(tag => `
                            <span class="bg-primary-100 text-primary-600 px-3 py-1 rounded-full text-sm">${this.escapeHtml(tag.tag_name)} (${tag.tag_count})</span>
                        `).join('') : '<p class="text-neutral-500">No popular tags yet.</p>'}
                    </div>
                </div>
                <div class="mt-6" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-xl font-semibold text-neutral-700 mb-4">Active Users</h3>
                    <div class="space-y-2">
                        ${active_users && active_users.length ? active_users.map(user => `
                            <div class="flex items-center space-x-2">
                                <img src="${user.profile_picture}" alt="Profile" class="w-10 h-10 rounded-full">
                                <span class="text-neutral-800">${this.escapeHtml(user.display_name)}</span>
                                <span class="text-sm text-neutral-500">(${user.post_count} posts)</span>
                            </div>
                        `).join('') : '<p class="text-neutral-500">No active users yet.</p>'}
                    </div>
                </div>
            `;

            AOS.refresh();
        } catch (error) {
            exploreContent.innerHTML = `
                <div class="glass-effect rounded-2xl shadow-lg p-8 max-w-md mx-auto elegant-card text-center" data-aos="zoom-in">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-neutral-700 mb-2">Error loading explore data</h3>
                    <p class="text-neutral-500 mb-4">Something went wrong. Please try again later.</p>
                    <button id="retry-explore" class="gradient-bg text-white px-4 py-2 rounded-lg font-medium shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 animate-ripple">
                        <i class="fas fa-redo mr-2"></i> Retry
                    </button>
                </div>
            `;
            document.getElementById('retry-explore')?.addEventListener('click', () => this.loadExplore(true));
            console.error('Error in loadExplore:', error.message, error.stack);
            AOS.refresh();
        }
    }

    async loadNotifications(forceRefresh = false) {
        let notificationsContainer = document.getElementById('notifications-container');
        if (!notificationsContainer) {
            console.warn('Notifications container not found, creating fallback');
            notificationsContainer = document.createElement('div');
            notificationsContainer.id = 'notifications-container';
            notificationsContainer.className = 'space-y-4';
            const notificationsSection = document.getElementById('notifications-section');
            if (notificationsSection) {
                notificationsSection.appendChild(notificationsContainer);
            } else {
                this.showToast('Error: Notifications section not found', 'error');
                return;
            }
        }

        notificationsContainer.innerHTML = `
            <div class="inline-flex items-center justify-center glass-effect rounded-2xl shadow px-6 py-4">
                <div class="animate-pulse-soft mr-3">
                    <div class="h-5 w-5 bg-primary-400 rounded-full"></div>
                </div>
                <p class="text-neutral-600 font-medium">Loading notifications...</p>
            </div>
        `;

        try {
            if (forceRefresh || !this.notifications) {
                const data = await this.apiRequest(`fetch_notifications.php?anon_id=${encodeURIComponent(this.anonId)}`);
                this.notifications = data.notifications || [];
                const badge = document.getElementById('notification-badge');
                if (badge) {
                    badge.innerHTML = data.unread_count > 0 ? data.unread_count : '';
                    badge.classList.toggle('hidden', data.unread_count === 0);
                }
            }

            if (this.notifications.length === 0) {
                notificationsContainer.innerHTML = `
                    <div class="glass-effect rounded-2xl shadow-lg p-8 max-w-md mx-auto elegant-card text-center" data-aos="zoom-in">
                        <div class="w-16 h-16 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-4 floating-element">
                            <i class="fas fa-bell-slash text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-neutral-700 mb-2">No new notifications</h3>
                        <p class="text-neutral-500 mb-4">You're all caught up!</p>
                    </div>
                `;
                AOS.refresh();
                return;
            }

            notificationsContainer.innerHTML = this.notifications.map(notification => `
                <div class="glass-effect rounded-2xl shadow-xl p-6 elegant-card ${notification.is_read ? 'opacity-75' : ''}" data-aos="fade-up">
                    <div class="flex items-center space-x-2">
                        <img src="${notification.source_profile_picture || 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A'}" alt="Profile" class="w-8 h-8 rounded-full">
                        <p class="text-neutral-800">
                            <span class="font-semibold">${this.escapeHtml(notification.source_display_name || 'Anonymous')}</span>
                            ${notification.type === 'like' ? 'liked your post' : 
                              notification.type === 'comment' ? 'commented on your post' : 
                              notification.type === 'mention' ? 'mentioned you' : 
                              'followed you'}
                        </p>
                    </div>
                    ${notification.post_content ? `<p class="text-sm text-neutral-600 mt-1">"${this.escapeHtml(notification.post_content.substring(0, 50))}..."</p>` : ''}
                    ${notification.comment_content ? `<p class="text-sm text-neutral-600 mt-1">"${this.escapeHtml(notification.comment_content.substring(0, 50))}..."</p>` : ''}
                    <span class="text-xs text-neutral-500">${this.getTimeAgo(new Date(notification.created_at))}</span>
                </div>
            `).join('');

            AOS.refresh();
        } catch (error) {
            notificationsContainer.innerHTML = `
                <div class="glass-effect rounded-2xl shadow-lg p-8 max-w-md mx-auto elegant-card text-center" data-aos="zoom-in">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-neutral-700 mb-2">Error loading notifications</h3>
                    <p class="text-neutral-500 mb-4">Something went wrong. Please try again later.</p>
                    <button id="retry-notifications" class="gradient-bg text-white px-4 py-2 rounded-lg font-medium shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 animate-ripple">
                        <i class="fas fa-redo mr-2"></i> Retry
                    </button>
                </div>
            `;
            document.getElementById('retry-notifications')?.addEventListener('click', () => this.loadNotifications(true));
            console.error('Error in loadNotifications:', error.message, error.stack);
            AOS.refresh();
        }
    }

    async loadProfile(forceRefresh = false) {
        let profileContent = document.getElementById('profile-content');
        if (!profileContent) {
            console.warn('Profile content container not found, creating fallback');
            profileContent = document.createElement('div');
            profileContent.id = 'profile-content';
            profileContent.className = 'space-y-8';
            const profileSection = document.getElementById('profile-section');
            if (profileSection) {
                profileSection.appendChild(profileContent);
            } else {
                this.showToast('Error: Profile section not found', 'error');
                return;
            }
        }

        profileContent.innerHTML = `
            <div class="inline-flex items-center justify-center glass-effect rounded-2xl shadow px-6 py-4">
                <div class="animate-pulse-soft mr-3">
                    <div class="h-5 w-5 bg-primary-400 rounded-full"></div>
                </div>
                <p class="text-neutral-600 font-medium">Loading your profile...</p>
            </div>
        `;

        try {
            if (forceRefresh || !this.profileData) {
                this.profileData = await this.apiRequest(`fetch_profile.php?anon_id=${encodeURIComponent(this.anonId)}`);
            }

            const { profile, stats, recent_posts } = this.profileData;

            profileContent.innerHTML = `
                <div class="glass-effect rounded-2xl shadow-xl p-6 elegant-card" data-aos="fade-up">
                    <div class="flex items-center space-x-4 mb-4">
                        <img id="profile-picture-display" src="${profile.profile_picture || 'https://via.placeholder.com/100x100/F3F4F6/6B7280?text=A'}" alt="Profile Picture" class="w-24 h-24 rounded-full object-cover border-4 border-primary-300 shadow-md">
                        <div>
                            <h3 id="profile-display-name" class="text-2xl font-bold text-neutral-800">${this.escapeHtml(profile.display_name)}</h3>
                            <p id="user-id" class="text-neutral-500 text-sm">
                                <span class="font-semibold">ID:</span> <span id="profile-anon-id">${this.escapeHtml(profile.anon_id.substring(0, 12))}...</span>
                                <button id="copy-id-btn" class="ml-2 text-primary-500 hover:text-primary-700 text-xs"><i class="fas fa-copy"></i> Copy</button>
                            </p>
                            <p id="profile-created-at" class="text-neutral-500 text-sm">Joined: ${new Date(profile.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-center mt-6">
                        <div>
                            <p id="profile-post-count" class="text-2xl font-bold gradient-text">${stats.post_count || 0}</p>
                            <p class="text-neutral-500 text-sm">Posts</p>
                        </div>
                        <div>
                            <p id="profile-comment-count" class="text-2xl font-bold gradient-text">${stats.comment_count || 0}</p>
                            <p class="text-neutral-500 text-sm">Comments</p>
                        </div>
                        <div>
                            <p id="profile-received-likes" class="text-2xl font-bold gradient-text">${stats.received_likes || 0}</p>
                            <p class="text-neutral-500 text-sm">Received Likes</p>
                        </div>
                    </div>
                    <button id="edit-profile-btn" class="mt-6 gradient-bg text-white px-5 py-2 rounded-xl font-medium shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 animate-ripple">
                        <i class="fas fa-edit mr-2"></i> Edit Profile
                    </button>
                </div>
            `;

            const recentPostsContainer = document.getElementById('profile-recent-posts-container');
            if (recentPostsContainer) {
                if (recent_posts && recent_posts.length > 0) {
                    recentPostsContainer.innerHTML = recent_posts.map(post => `
                        <div class="post-card glass-effect rounded-2xl shadow-xl p-6 elegant-card" data-aos="fade-up">
                            <p class="text-neutral-800">${this.escapeHtml(post.content)}</p>
                            ${post.image ? `<img src="${post.image}" alt="Post image" class="mt-2 rounded-lg max-w-full h-auto">` : ''}
                            <div class="flex justify-between items-center mt-3">
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-neutral-500"><i class="fas fa-heart text-red-500"></i> ${post.like_count || 0}</span>
                                    <span class="text-sm text-neutral-500"><i class="fas fa-comment text-blue-500"></i> ${post.comment_count || 0}</span>
                                </div>
                                <span class="text-xs text-neutral-500">${this.getTimeAgo(new Date(post.created_at))}</span>
                            </div>
                        </div>
                    `).join('');
                } else {
                    recentPostsContainer.innerHTML = `
                        <div class="text-center py-8" data-aos="zoom-in">
                            <p class="text-neutral-500">You haven't made any posts yet.</p>
                        </div>
                    `;
                }
            }

            const profileForm = document.getElementById('profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.saveProfile();
                });
            }

            const copyIdBtn = document.getElementById('copy-id-btn');
            if (copyIdBtn) copyIdBtn.addEventListener('click', () => this.copyAnonId());

            AOS.refresh();
        } catch (error) {
            profileContent.innerHTML = `
                <div class="glass-effect rounded-2xl shadow-lg p-8 max-w-md mx-auto elegant-card text-center" data-aos="zoom-in">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-neutral-700 mb-2">Error loading profile</h3>
                    <p class="text-neutral-500 mb-4">Something went wrong. Please try again later.</p>
                    <button id="retry-profile" class="gradient-bg text-white px-4 py-2 rounded-lg font-medium shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 animate-ripple">
                        <i class="fas fa-redo mr-2"></i> Retry
                    </button>
                </div>
            `;
            document.getElementById('retry-profile')?.addEventListener('click', () => this.loadProfile(true));
            console.error('Error in loadProfile:', error.message, error.stack);
            AOS.refresh();
        }
    }

    async toggleLike(postId) {
        try {
            const data = await this.apiRequest('like_post.php', {
                method: 'POST',
                body: JSON.stringify({ anon_id: this.anonId, post_id: postId })
            });
            this.showToast(data.liked ? 'Post liked!' : 'Post unliked!', 'success');
            await this.loadView(this.currentView, true);
        } catch (error) {
            console.error('Error toggling like:', error);
            this.showToast('Error toggling like', 'error');
        }
    }

    openCommentModal(postId) {
        this.currentCommentPostId = postId;
        const commentModal = document.getElementById('comment-modal');
        const commentContent = document.getElementById('comment-content');
        const commentMedia = document.getElementById('comment-media');
        if (commentModal && commentContent) {
            commentModal.classList.remove('hidden');
            commentContent.value = '';
            if (commentMedia) commentMedia.value = '';
            document.getElementById('comment-char-count').textContent = '0/500 characters';
            document.getElementById('submit-comment').disabled = true;
            commentContent.focus();
        }
    }

    closeCommentModal() {
        const commentModal = document.getElementById('comment-modal');
        if (commentModal) {
            commentModal.classList.add('hidden');
            this.currentCommentPostId = null;
        }
    }

    async submitComment() {
        const commentContent = document.getElementById('comment-content');
        const commentMedia = document.getElementById('comment-media');
        if (!commentContent || !this.currentCommentPostId) {
            this.showToast('Error: Cannot submit comment', 'error');
            return;
        }

        const content = commentContent.value.trim();
        if (content.length === 0 || content.length > 500) {
            this.showToast('Comment must be between 1 and 500 characters', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('anon_id', this.anonId);
        formData.append('post_id', this.currentCommentPostId);
        formData.append('content', content);
        if (commentMedia && commentMedia.files[0]) {
            formData.append('media', commentMedia.files[0]);
        }
        // Extract tags from content (e.g., #tag)
        const tags = content.match(/#[^\s#]+/g)?.map(tag => tag.substring(1)) || [];
        formData.append('tags', JSON.stringify(tags));

        try {
            await this.apiRequest('comment_post.php', {
                method: 'POST',
                body: formData
            });
            this.showToast('Comment submitted successfully!', 'success');
            this.closeCommentModal();
            await this.loadView(this.currentView, true);
        } catch (error) {
            console.error('Error submitting comment:', error);
            this.showToast(`Error submitting comment: ${error.message}`, 'error');
        }
    }

    async submitPost() {
        const postContent = document.getElementById('post-content');
        const postMedia = document.getElementById('post-media');
        if (!postContent || !this.anonId) {
            this.showToast('Error: Cannot submit post', 'error');
            return;
        }

        const content = postContent.value.trim();
        if (content.length === 0 || content.length > 1000) {
            this.showToast('Post content must be between 1 and 1000 characters', 'error');
            return;
        }

        // Use FormData for file uploads
        const formData = new FormData();
        formData.append('anon_id', this.anonId);
        formData.append('content', content);
        if (postMedia && postMedia.files[0]) {
            formData.append('image', postMedia.files[0]);
        }

        try {
            await this.apiRequest('submit_post.php', {
                method: 'POST',
                body: formData
                // Don't set Content-Type header for FormData - browser will set it automatically
            });
            this.showToast('Post submitted successfully!', 'success');
            postContent.value = '';
            if (postMedia) postMedia.value = '';
            document.getElementById('char-count').textContent = '0/1000 characters';
            document.getElementById('submit-btn').disabled = true;
            await this.loadView('feed', true);
        } catch (error) {
            console.error('Error submitting post:', error);
            this.showToast(`Error submitting post: ${error.message}`, 'error');
        }
    }

    async saveProfile() {
        const form = document.getElementById('profile-form');
        if (!form) {
            this.showToast('Error: Profile form not found', 'error');
            return;
        }

        const formData = new FormData(form);
        formData.append('anon_id', this.anonId);

        try {
            await this.apiRequest('update_profile.php', {
                method: 'POST',
                body: formData
            });
            this.showToast('Profile updated successfully!', 'success');
            this.toggleEditProfile(false);
            await this.loadProfile(true);
        } catch (error) {
            console.error('Error updating profile:', error);
            this.showToast(`Error updating profile: ${error.message}`, 'error');
        }
    }

    toggleEditProfile(show) {
        const profileContent = document.getElementById('profile-content');
        const editProfileForm = document.getElementById('edit-profile-form');

        if (!profileContent || !editProfileForm) return;

        if (show) {
            profileContent.classList.add('hidden');
            editProfileForm.classList.remove('hidden');
            document.getElementById('edit-display-name').value = this.profileData?.profile.display_name || 'Anonymous User';
        } else {
            profileContent.classList.remove('hidden');
            editProfileForm.classList.add('hidden');
        }
    }

    showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return;

        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-secondary-500' :
                        type === 'error' ? 'bg-red-500' : 'bg-primary-500';

        toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 glass-effect toast`;
        toast.textContent = message;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);

        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }

    getTimeAgo(date) {
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.uniWhisperApp = new UniWhisper();
});