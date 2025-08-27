// UniWhisper JavaScript Application
class UniWhisper {
    constructor() {
        // API base URL - adjust for your server
        //this.apiBase = 'http://localhost:8000'; // For php -S localhost:8000
        this.apiBase = 'http://localhost/uniwhisper'; // Uncomment for Apache
        
        // User management
        this.anonId = null;
        this.currentCommentPostId = null;
        
        // Initialize the application
        this.init();
    }
    
    async init() {
        console.log('Initializing UniWhisper...');
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Initialize user
        await this.initializeUser();
        
        // Load posts
        await this.loadPosts();
        
        console.log('UniWhisper initialized successfully');
    }
    
    setupEventListeners() {
        const postForm = document.getElementById('post-form');
        const postContent = document.getElementById('post-content');
        const submitBtn = document.getElementById('submit-btn');
        const charCount = document.getElementById('char-count');
        
        if (!postForm || !postContent || !submitBtn || !charCount) {
            console.error('Post form elements missing:', {
                postForm: !!postForm,
                postContent: !!postContent,
                submitBtn: !!submitBtn,
                charCount: !!charCount
            });
            this.showToast('Error: Post form elements not found', 'error');
            return;
        }
        
        postContent.addEventListener('input', () => {
            const length = postContent.value.length;
            charCount.textContent = length;
            submitBtn.disabled = length === 0 || length > 1000;
        });
        
        postForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitPost();
        });
        
        const commentModal = document.getElementById('comment-modal');
        const commentContent = document.getElementById('comment-content');
        const commentCharCount = document.getElementById('comment-char-count');
        const submitCommentBtn = document.getElementById('submit-comment');
        const cancelCommentBtn = document.getElementById('cancel-comment');
        
        if (!commentModal || !commentContent || !commentCharCount || !submitCommentBtn || !cancelCommentBtn) {
            console.error('Comment modal elements missing:', {
                commentModal: !!commentModal,
                commentContent: !!commentContent,
                commentCharCount: !!commentCharCount,
                submitCommentBtn: !!submitCommentBtn,
                cancelCommentBtn: !!cancelCommentBtn
            });
            this.showToast('Error: Comment modal elements not found', 'error');
            return;
        }
        
        commentContent.addEventListener('input', () => {
            const length = commentContent.value.length;
            commentCharCount.textContent = length;
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
    
    async initializeUser() {
        this.anonId = localStorage.getItem('uniwhisper_anon_id');
        
        if (this.anonId) {
            try {
                const response = await fetch(`${this.apiBase}/check_user.php?anon_id=${encodeURIComponent(this.anonId)}`);
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();
                
                if (!data.success || !data.exists) {
                    await this.createNewUser();
                }
            } catch (error) {
                console.error('Error checking user:', error.message, error.stack);
                await this.createNewUser();
            }
        } else {
            await this.createNewUser();
        }
        
        this.updateUserStatus();
    }
    
    async createNewUser() {
        try {
            const response = await fetch(`${this.apiBase}/register_user.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }
            const data = await response.json();
            
            if (data.success) {
                this.anonId = data.anon_id;
                localStorage.setItem('uniwhisper_anon_id', this.anonId);
                console.log('New anonymous user created:', this.anonId);
            } else {
                throw new Error(data.error || 'Failed to create user');
            }
        } catch (error) {
            console.error('Error creating user:', error.message, error.stack);
            this.showToast(`Error creating anonymous user: ${error.message}`, 'error');
        }
    }
    
    updateUserStatus() {
        const userStatus = document.getElementById('user-status');
        if (!userStatus) {
            console.error('User status element missing');
            this.showToast('Error: User status element not found', 'error');
            return;
        }
        if (this.anonId) {
            userStatus.textContent = `Anonymous User (${this.anonId.substring(0, 12)}...)`;
        }
    }
    
    async submitPost() {
        const postContent = document.getElementById('post-content');
        const charCount = document.getElementById('char-count');
        const submitBtn = document.getElementById('submit-btn');
        
        if (!postContent || !charCount || !submitBtn) {
            console.error('Post form elements missing:', {
                postContent: !!postContent,
                charCount: !!charCount,
                submitBtn: !!submitBtn
            });
            this.showToast('Error: Post form elements not found', 'error');
            return;
        }
        
        const content = postContent.value.trim();
        
        if (!content || !this.anonId) {
            this.showToast('Please enter some content', 'error');
            return;
        }
        
        try {
            const response = await fetch(`${this.apiBase}/submit_post.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ anon_id: this.anonId, content })
            });
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }
            const data = await response.json();
            
            if (data.success) {
                postContent.value = '';
                charCount.textContent = '0';
                submitBtn.disabled = true;
                this.showToast('Post shared successfully!', 'success');
                await this.loadPosts();
            } else {
                throw new Error(data.error || 'Failed to submit post');
            }
        } catch (error) {
            console.error('Error submitting post:', error.message, error.stack);
            this.showToast(`Error submitting post: ${error.message}`, 'error');
        }
    }
    
    async loadPosts() {
        const postsSection = document.getElementById('posts');
        const loadingPosts = document.getElementById('loading-posts');
        const noPosts = document.getElementById('no-posts');
        
        // Log missing elements for debugging
        if (!postsSection || !loadingPosts || !noPosts) {
            console.error('Post section elements missing:', {
                postsSection: !!postsSection,
                loadingPosts: !!loadingPosts,
                noPosts: !!noPosts
            });
            // Only show error toast if postsSection is missing
            if (!postsSection) {
                this.showToast('Error: Posts container not found', 'error');
                return;
            }
        }
        
        try {
            // Show loading indicator if available
            if (loadingPosts) {
                loadingPosts.classList.remove('hidden');
            }
            if (noPosts) {
                noPosts.classList.add('hidden');
            }
            
            const response = await fetch(`${this.apiBase}/fetch_posts.php`);
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to load posts');
            }
            
            if (data.posts.length === 0) {
                if (noPosts) {
                    noPosts.classList.remove('hidden');
                }
                postsSection.innerHTML = '';
            } else {
                postsSection.innerHTML = data.posts.map(post => {
                    const timeAgo = this.getTimeAgo(new Date(post.created_at));
                    const commentsHtml = this.renderComments(post.comments || []);
                    
                    return `
                        <div class="bg-white p-6 rounded-lg shadow">
                            <p class="text-gray-800">${this.escapeHtml(post.content)}</p>
                            <div class="flex justify-between items-center mt-4">
                                <div class="flex space-x-4">
                                    <button class="like-btn flex items-center text-gray-500" data-post-id="${post.id}">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    <span class="like-count">${post.like_count || 0}</span>
                                </button>
                                <button class="comment-btn text-gray-500" data-post-id="${post.id}">
                                    <svg class="w-5 h-5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                    ${post.comment_count || 0}
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">${timeAgo}</p>
                        </div>
                        ${commentsHtml}
                    </div>
                `;
                }).join('');
                
                document.querySelectorAll('.like-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const postId = btn.getAttribute('data-post-id');
                        this.toggleLike(postId, btn);
                    });
                });
                
                document.querySelectorAll('.comment-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const postId = btn.getAttribute('data-post-id');
                        this.openCommentModal(postId);
                    });
                });
            }
        } catch (error) {
            console.error('Error loading posts:', error.message, error.stack);
            this.showToast(`Error loading posts: ${error.message}`, 'error');
        } finally {
            if (loadingPosts) {
                loadingPosts.classList.add('hidden');
            }
        }
    }
    
    renderComments(comments) {
        if (!comments || comments.length === 0) return '';
        
        const commentsHtml = comments.map(comment => {
            const timeAgo = this.getTimeAgo(new Date(comment.created_at));
            return `
                <div class="flex space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-sm">
                            A
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-sm text-gray-800">${this.escapeHtml(comment.content)}</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">${timeAgo}</p>
                    </div>
                </div>
            `;
        }).join('');
        
        return `
            <div class="mt-4 pt-4 border-t border-gray-100">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Comments</h4>
                <div class="space-y-2">
                    ${commentsHtml}
                </div>
            </div>
        `;
    }
    
    async toggleVote(postId, voteType, voteBtn) {
        if (!this.anonId) {
            this.showToast("Error: User not initialized", "error");
            return;
        }

        if (!voteBtn) {
            console.error("Vote button is null for postId:", postId);
            this.showToast("Error: Vote button not found", "error");
            return;
        }

        try {
            const response = await fetch(`${this.apiBase}/vote.php`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ anon_id: this.anonId, post_id: postId, vote_type: voteType })
            });
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }
            const data = await response.json();

            if (data.success) {
                const upvoteCountElement = voteBtn.closest(".flex.space-x-4").querySelector(".upvote-count");
                const downvoteCountElement = voteBtn.closest(".flex.space-x-4").querySelector(".downvote-count");

                if (upvoteCountElement) upvoteCountElement.textContent = data.upvotes;
                if (downvoteCountElement) downvoteCountElement.textContent = data.downvotes;

                // Update button styles based on current vote status (this would require more complex logic
                // to track user's current vote, but for simplicity, we'll just update counts)
                this.showToast(`Post ${voteType}d successfully!`, "success");
            } else {
                throw new Error(data.error || "Failed to toggle vote");
            }
        } catch (error) {
            console.error("Error toggling vote:", error.message, error.stack);
            this.showToast(`Error updating vote: ${error.message}`, "error");
        }
    }
    
    openCommentModal(postId) {
        this.currentCommentPostId = postId;
        const modal = document.getElementById('comment-modal');
        const commentContent = document.getElementById('comment-content');
        
        if (!modal || !commentContent) {
            console.error('Comment modal elements missing:', {
                modal: !!modal,
                commentContent: !!commentContent
            });
            this.showToast('Error: Comment modal elements not found', 'error');
            return;
        }
        
        commentContent.value = '';
        const commentCharCount = document.getElementById('comment-char-count');
        const submitCommentBtn = document.getElementById('submit-comment');
        
        if (!commentCharCount || !submitCommentBtn) {
            console.error('Comment modal elements missing:', {
                commentCharCount: !!commentCharCount,
                submitCommentBtn: !!submitCommentBtn
            });
            this.showToast('Error: Comment modal elements not found', 'error');
            return;
        }
        
        commentCharCount.textContent = '0';
        submitCommentBtn.disabled = true;
        
        modal.classList.remove('hidden');
        commentContent.focus();
    }
    
    closeCommentModal() {
        const modal = document.getElementById('comment-modal');
        if (!modal) {
            console.error('Comment modal element missing');
            this.showToast('Error: Comment modal element not found', 'error');
            return;
        }
        modal.classList.add('hidden');
        this.currentCommentPostId = null;
    }
    
    async submitComment() {
        const commentContent = document.getElementById('comment-content');
        if (!commentContent) {
            console.error('Comment content element missing');
            this.showToast('Error: Comment content element not found', 'error');
            return;
        }
        
        const content = commentContent.value.trim();
        
        if (!content || !this.anonId || !this.currentCommentPostId) {
            this.showToast('Please enter a comment', 'error');
            return;
        }
        
        try {
            const response = await fetch(`${this.apiBase}/comment_post.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    anon_id: this.anonId,
                    post_id: this.currentCommentPostId,
                    content
                })
            });
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }
            const data = await response.json();
            
            if (data.success) {
                this.closeCommentModal();
                this.showToast('Comment added successfully!', 'success');
                await this.loadPosts();
            } else {
                throw new Error(data.error || 'Failed to submit comment');
            }
        } catch (error) {
            console.error('Error submitting comment:', error.message, error.stack);
            this.showToast(`Error submitting comment: ${error.message}`, 'error');
        }
    }
    
    showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            console.error('Toast container element missing');
            return;
        }
        
        const toast = document.createElement('div');
        
        const bgColor = type === 'success' ? 'bg-green-500' : 
                       type === 'error' ? 'bg-red-500' : 'bg-blue-500';
        
        toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
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
    async fetchExplore() {
        try {
            const response = await fetch(`${this.apiBase}/fetch_explore.php`);
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                return data;
            } else {
                throw new Error(data.error || 'Failed to fetch explore data');
            }
        } catch (error) {
            console.error('Error fetching explore data:', error);
            this.showToast(`Error loading explore: ${error.message}`, 'error');
            return null;
        }
    }

    async fetchNotifications() {
        if (!this.anonId) return null;
        
        try {
            const response = await fetch(`${this.apiBase}/fetch_notifications.php?anon_id=${encodeURIComponent(this.anonId)}`);
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                return data.notifications;
            } else {
                throw new Error(data.error || 'Failed to fetch notifications');
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
            this.showToast(`Error loading notifications: ${error.message}`, 'error');
            return null;
        }
    }

    async fetchProfile(anonId = this.anonId)

    async reportPost(postId) {
        try {
            const reason = prompt("Please enter a reason for reporting this post (optional):");
            const data = await this.apiRequest("report_post.php", {
                method: "POST",
                body: JSON.stringify({
                    post_id: postId,
                    anon_id: this.anonId,
                    reason: reason || "No reason provided"
                })
            });
            this.showToast(data.message, "success");
        } catch (error) {
            console.error("Error reporting post:", error);
            this.showToast(`Error reporting post: ${error.message}`, "error");
        }
    }




    async redeemPoints(feature) {
        try {
            const data = await this.apiRequest("redeem_points.php", {
                method: "POST",
                body: JSON.stringify({
                    anon_id: this.anonId,
                    feature: feature
                })
            });
            this.showToast(data.message, "success");
            // Optionally, refresh profile data to show updated points
            await this.loadProfile(true);
        } catch (error) {
            console.error("Error redeeming points:", error);
            this.showToast(`Error redeeming points: ${error.message}`, "error");
        }
    }


