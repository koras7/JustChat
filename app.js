// app.js - Main application logic

let currentUser = null;

// Session timeout (30 minutes)
let sessionTimeout;
const SESSION_TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes

function resetSessionTimeout() {
    clearTimeout(sessionTimeout);
    sessionTimeout = setTimeout(() => {
        alert('Your session has expired due to inactivity. Please login again.');
        localStorage.removeItem('session_token');
        localStorage.removeItem('user_data');
        location.reload();
    }, SESSION_TIMEOUT_MS);
}


// Check if logged in
const sessionToken = localStorage.getItem('session_token');
const userData = localStorage.getItem('user_data');

// Reset timeout on user activity
if (sessionToken && userData) {
    resetSessionTimeout();
    document.addEventListener('click', resetSessionTimeout);
    document.addEventListener('keypress', resetSessionTimeout);
}

if (sessionToken && userData) {
    currentUser = JSON.parse(userData);
    loadProfile();
}

// Show/Hide screens
document.getElementById('showRegister').onclick = () => {
    document.getElementById('loginScreen').classList.add('hidden');
    document.getElementById('registerScreen').classList.remove('hidden');
};

document.getElementById('showLogin').onclick = () => {
    document.getElementById('registerScreen').classList.add('hidden');
    document.getElementById('loginScreen').classList.remove('hidden');
};
// Password strength indicator for registration
setTimeout(() => {
    const regPasswordInput = document.getElementById('regPassword');
    if (regPasswordInput) {
        regPasswordInput.addEventListener('input', (e) => {
            const password = e.target.value;
            let strength = 0;
            let feedback = '';
            let color = '';
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (password.length === 0) {
                feedback = '';
            } else if (strength < 2) {
                feedback = '❌ Weak - Add more characters';
                color = '#c33';
            } else if (strength < 4) {
                feedback = '⚠️ Medium - Add numbers or symbols';
                color = '#f90';
            } else {
                feedback = '✅ Strong password';
                color = '#3c3';
            }
            
            let indicator = document.getElementById('passwordStrength');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'passwordStrength';
                indicator.style.cssText = 'font-size: 13px; margin-top: 5px;';
                e.target.parentElement.appendChild(indicator);
            }
            
            indicator.textContent = feedback;
            indicator.style.color = color;
        });
    }
}, 100);

// Register
document.getElementById('registerBtn').onclick = async () => {
    const fullName = document.getElementById('regFullName').value;
    const username = document.getElementById('regUsername').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPassword').value;
    
    document.getElementById('registerError').classList.add('hidden');
    document.getElementById('registerSuccess').classList.add('hidden');
    
    try {
        const response = await fetch('/justchat/api/register.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({full_name: fullName, username, email, password})
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('registerSuccess').textContent = 
                'Registration successful! Verifying...';
            document.getElementById('registerSuccess').classList.remove('hidden');
            
            // Auto verify
            await fetch('/justchat/api/verify-email.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({user_id: data.user_id, code: data.verification_code})
            });
            
            setTimeout(() => {
                alert('Account created and verified! Please login.');
                document.getElementById('showLogin').click();
            }, 1000);
        } else {
            document.getElementById('registerError').textContent = data.error;
            document.getElementById('registerError').classList.remove('hidden');
        }
    } catch (error) {
        document.getElementById('registerError').textContent = 'Network error';
        document.getElementById('registerError').classList.remove('hidden');
    }
};

// Login
document.getElementById('loginBtn').onclick = async () => {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
    document.getElementById('loginError').classList.add('hidden');
    
    try {
        const response = await fetch('/justchat/api/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email, password})
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('session_token', data.session_token);
            localStorage.setItem('user_data', JSON.stringify(data.user));
            currentUser = data.user;
            loadProfile();
        } else {
            document.getElementById('loginError').textContent = data.error;
            document.getElementById('loginError').classList.remove('hidden');
        }
    } catch (error) {
        document.getElementById('loginError').textContent = 'Network error';
        document.getElementById('loginError').classList.remove('hidden');
    }
};

// Logout
document.getElementById('logoutBtn').onclick = () => {
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_data');
    location.reload();
};

// Load Profile
async function loadProfile() {
    document.getElementById('loginScreen').classList.add('hidden');
    document.getElementById('registerScreen').classList.add('hidden');
    document.getElementById('profileScreen').classList.remove('hidden');
    document.getElementById('profileView').classList.remove('hidden');
    document.getElementById('directoryView').classList.add('hidden');
    document.getElementById('friendsView').classList.add('hidden');
    
    try {
        const response = await fetch(`/justchat/api/profile.php?user_id=${currentUser.id}`);
        const data = await response.json();
        
        if (data.success) {
            displayProfile(data.user);
        }
    } catch (error) {
        console.error('Failed to load profile');
    }
}

// Display Profile
function displayProfile(user) {
    // Avatar initial
    const initial = user.full_name.charAt(0).toUpperCase();
    const avatarEl = document.getElementById('avatarInitial');
    
    if (user.profile_image_path) {
        avatarEl.style.backgroundImage = `url('/${user.profile_image_path}')`;
        avatarEl.style.backgroundSize = 'cover';
        avatarEl.style.backgroundPosition = 'center';
        avatarEl.textContent = '';
    } else {
        avatarEl.style.backgroundImage = 'none';
        avatarEl.textContent = initial;
    }
    
    // Basic info
    document.getElementById('profileName').textContent = user.full_name;
    document.getElementById('profileUsername').textContent = '@' + user.username;
    document.getElementById('profileEmail').textContent = user.email;
    document.getElementById('profileUniversity').textContent = user.university || 'Not set';
    document.getElementById('profilePhone').textContent = user.phone || 'Not provided';
    document.getElementById('profileMajor').textContent = user.major || 'Not set';
    document.getElementById('profileYear').textContent = user.year || 'Not set';
    document.getElementById('profilePronouns').textContent = user.pronouns || 'Not set';
    document.getElementById('profileNickname').textContent = user.nickname || 'Not set';
    document.getElementById('profileBio').textContent = user.bio || 'No bio yet';
    document.getElementById('profileHobbies').textContent = user.hobbies || 'No hobbies listed';
    
    // Status
    const statusText = user.availability_status || 'Available';
    document.getElementById('profileStatus').textContent = '🟢 ' + statusText;
}

// Edit Profile
document.getElementById('editProfileBtn').onclick = async () => {
    const response = await fetch(`/justchat/api/profile.php?user_id=${currentUser.id}`);
    const data = await response.json();
    
    if (data.success) {
        const user = data.user;

                // Show current profile picture
        const initial = user.full_name.charAt(0).toUpperCase();
        document.getElementById('profilePicPlaceholder').textContent = initial;
        
        if (user.profile_image_path) {
            document.getElementById('currentProfilePic').src = '/' + user.profile_image_path;
            document.getElementById('currentProfilePic').style.display = 'block';
            document.getElementById('profilePicPlaceholder').style.display = 'none';
        } else {
            document.getElementById('currentProfilePic').style.display = 'none';
            document.getElementById('profilePicPlaceholder').style.display = 'flex';
        }
        
        document.getElementById('editFullName').value = user.full_name || '';
        document.getElementById('editFullName').value = user.full_name || '';
        document.getElementById('editNickname').value = user.nickname || '';
        document.getElementById('editUniversity').value = user.university || '';
        document.getElementById('editPhone').value = user.phone || '';
        document.getElementById('editMajor').value = user.major || '';
        document.getElementById('editYear').value = user.year || '';
        document.getElementById('editPronouns').value = user.pronouns || '';
        document.getElementById('editAvailability').value = user.availability_status || '';
        document.getElementById('editBio').value = user.bio || '';
        document.getElementById('editHobbies').value = user.hobbies || '';
        
        document.getElementById('editModal').classList.add('show');
    }
};

// Close Modal
document.getElementById('closeModal').onclick = () => {
    document.getElementById('editModal').classList.remove('show');
};
document.getElementById('cancelEdit').onclick = () => {
    document.getElementById('editModal').classList.remove('show');
};

// Save Profile
document.getElementById('saveProfile').onclick = async () => {
    document.getElementById('editError').classList.add('hidden');
    document.getElementById('editSuccess').classList.add('hidden');
    
    // Check if there's a new profile image
    const imageInput = document.getElementById('profileImageInput');
    if (imageInput.files.length > 0) {
        const formData = new FormData();
        formData.append('profile_image', imageInput.files[0]);
        
        try {
            const response = await fetch('/justchat/api/upload.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                document.getElementById('editError').textContent = 'Image upload failed: ' + data.error;
                document.getElementById('editError').classList.remove('hidden');
                return;
            }
        } catch (error) {
            document.getElementById('editError').textContent = 'Failed to upload image';
            document.getElementById('editError').classList.remove('hidden');
            return;
        }
    }
    
    // Update other profile fields
    const profileData = {
        full_name: document.getElementById('editFullName').value,
        nickname: document.getElementById('editNickname').value,
        university: document.getElementById('editUniversity').value,
        phone: document.getElementById('editPhone').value,
        major: document.getElementById('editMajor').value,
        year: document.getElementById('editYear').value,
        pronouns: document.getElementById('editPronouns').value,
        availability_status: document.getElementById('editAvailability').value,
        bio: document.getElementById('editBio').value,
        hobbies: document.getElementById('editHobbies').value
    };
    
    try {
        const response = await fetch('/justchat/api/profile.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(profileData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('editSuccess').textContent = 'Profile updated successfully!';
            document.getElementById('editSuccess').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('editModal').classList.remove('show');
                displayProfile(data.user);
                loadProfile(); // Reload to show new image
            }, 1500);
        } else {
            document.getElementById('editError').textContent = data.error;
            document.getElementById('editError').classList.remove('hidden');
        }
    } catch (error) {
        document.getElementById('editError').textContent = 'Failed to update profile';
        document.getElementById('editError').classList.remove('hidden');
    }
};

// Navigation
document.getElementById('myProfileBtn').onclick = () => {
    document.getElementById('directoryView').classList.add('hidden');
    document.getElementById('friendsView').classList.add('hidden');
    document.getElementById('profileView').classList.remove('hidden');
};

document.getElementById('friendsBtn').onclick = () => {
    showFriends();
};

document.getElementById('directoryBtn').onclick = () => {
    showDirectory();
};

// Show Directory
async function showDirectory() {
    document.getElementById('profileView').classList.add('hidden');
    document.getElementById('friendsView').classList.add('hidden');
    document.getElementById('directoryView').classList.remove('hidden');
    
    try {
        const response = await fetch('/justchat/api/directory.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('directoryUniversity').textContent = 
                `Students at ${data.university} (${data.count} found)`;
            displayStudents(data.students);
        }
    } catch (error) {
        console.error('Failed to load directory', error);
        document.getElementById('studentList').innerHTML = 
            '<p style="text-align: center; color: #c33; padding: 40px;">Failed to load directory</p>';
    }
}

// Display Students
function displayStudents(students) {
    const listDiv = document.getElementById('studentList');
    
    if (students.length === 0) {
        listDiv.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">No students found</p>';
        return;
    }
    
    listDiv.innerHTML = students.map(student => {
        const initial = student.full_name.charAt(0).toUpperCase();
        const status = student.availability_status || 'Available';
        
        return `
            <div class="student-card" style="cursor: pointer;">
            <div class="student-avatar" onclick="viewUserProfile(${student.id}); event.stopPropagation();">${initial}</div>
            <div class="student-info" onclick="viewUserProfile(${student.id}); event.stopPropagation();">
                    <div class="student-name">${student.full_name}</div>
                    <div class="student-details">
                        @${student.username}
                        ${student.major ? ' • ' + student.major : ''}
                        ${student.year ? ' • ' + student.year : ''}
                    </div>
                    <div class="student-status">🟢 ${status}</div>
                </div>
                <button onclick="sendFriendRequest(${student.id}, '${student.full_name}')" 
                        style="width: auto; padding: 8px 16px; font-size: 14px;">
                    👋 Add Friend
                </button>
            </div>
        `;
    }).join('');
}

// Search functionality
document.getElementById('searchBtn').onclick = async () => {
    const search = document.getElementById('searchStudents').value;
    const major = document.getElementById('filterMajor').value;
    const year = document.getElementById('filterYear').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (major) params.append('major', major);
    if (year) params.append('year', year);
    
    try {
        const response = await fetch('/justchat/api/directory.php?' + params.toString());
        const data = await response.json();
        
        if (data.success) {
            displayStudents(data.students);
        }
    } catch (error) {
        console.error('Search failed');
    }
};

// Search on Enter key
document.getElementById('searchStudents').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        document.getElementById('searchBtn').click();
    }
});

// Send Friend Request
async function sendFriendRequest(userId, userName) {
    if (!confirm(`Send friend request to ${userName}?`)) {
        return;
    }
    
    try {
        const response = await fetch('/justchat/api/friends.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'send',
                friend_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Friend added! ✅');
            // Refresh directory to update button states
            showDirectory();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Full error:', error);
        alert('Failed to send request: ' + error.message);
    }
}

// Show Friends
async function showFriends() {
    document.getElementById('profileView').classList.add('hidden');
    document.getElementById('directoryView').classList.add('hidden');
    document.getElementById('friendsView').classList.remove('hidden');
    
    try {
        const response = await fetch('/justchat/api/friends.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('friendsCount').textContent = 
                `You have ${data.count} friend${data.count !== 1 ? 's' : ''}`;
            displayFriends(data.data);
        }
    } catch (error) {
        console.error('Failed to load friends', error);
        document.getElementById('friendsList').innerHTML = 
            '<p style="text-align: center; color: #c33; padding: 40px;">Failed to load friends</p>';
    }
}

// Display Friends
function displayFriends(friends) {
    const listDiv = document.getElementById('friendsList');
    
    if (friends.length === 0) {
        listDiv.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <p style="color: #999; margin-bottom: 15px;">You haven't added any friends yet</p>
                <button onclick="document.getElementById('directoryBtn').click()" 
                        style="width: auto; padding: 10px 20px;">
                    Browse Directory
                </button>
            </div>
        `;
        return;
    }
    
    listDiv.innerHTML = friends.map(friend => {
        const initial = friend.full_name.charAt(0).toUpperCase();
        
        return `
            <div class="student-card" onclick="viewFriendProfile(${friend.id})" style="cursor: pointer;">
                <div class="student-avatar">${initial}</div>
                <div class="student-info">
                    <div class="student-name">${friend.full_name}</div>
                    <div class="student-details">
                        @${friend.username}
                        ${friend.major ? ' • ' + friend.major : ''}
                        ${friend.year ? ' • ' + friend.year : ''}
                    </div>
                    <div class="student-status">✅ Friends</div>
                </div>
                <button onclick="openChat(${friend.id}, '${friend.full_name}')"
                        style="width: auto; padding: 8px 16px; font-size: 14px;">
                    💬 Message
                </button>
            </div>
        `;
    }).join('');
}
// Chat functionality
let currentChatUser = null;
let chatInterval = null;

// Open Chat
function openChat(userId, userName) {
    currentChatUser = userId;
    document.getElementById('chatWithName').textContent = `Chat with ${userName}`;
    document.getElementById('chatModal').classList.add('show');
    document.getElementById('messageInput').value = '';
    
    // Load messages
    loadMessages();
    
    // Auto-refresh messages every 3 seconds
    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(loadMessages, 3000);
}

// Close Chat
document.getElementById('closeChatModal').onclick = () => {
    document.getElementById('chatModal').classList.remove('show');
    if (chatInterval) {
        clearInterval(chatInterval);
        chatInterval = null;
    }
};

// Load Messages
async function loadMessages() {
    if (!currentChatUser) return;
    
    try {
        const response = await fetch(`/justchat/api/messages.php?with_user=${currentChatUser}`);
        const data = await response.json();
        
        if (data.success) {
            displayMessages(data.messages);
        }
    } catch (error) {
        console.error('Failed to load messages', error);
    }
}

// Display Messages
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    
    if (messages.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">No messages yet. Start the conversation!</p>';
        return;
    }
    
    container.innerHTML = messages.map(msg => {
        const isSent = msg.sender_id == currentUser.id;
        const messageClass = isSent ? 'sent' : 'received';
        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        return `
            <div class="message ${messageClass}">
                ${!isSent ? `<div class="message-sender">${msg.sender_name}</div>` : ''}
                <div class="message-bubble">${msg.content_text}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
    }).join('');
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
}

// Send Message
document.getElementById('sendMessageBtn').onclick = async () => {
    const messageText = document.getElementById('messageInput').value.trim();
    
    if (!messageText || !currentChatUser) return;
    
    try {
        const response = await fetch('/justchat/api/messages.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                recipient_id: currentChatUser,
                content_text: messageText
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('messageInput').value = '';
            loadMessages();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Failed to send message');
    }
};

// Send message on Enter key
document.getElementById('messageInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        document.getElementById('sendMessageBtn').click();
    }
});
// Show Change Password Modal
function showChangePassword() {
    document.getElementById('changePasswordModal').classList.add('show');
    document.getElementById('currentPassword').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    document.getElementById('changePasswordError').classList.add('hidden');
    document.getElementById('changePasswordSuccess').classList.add('hidden');
}

// Password strength for new password
document.addEventListener('DOMContentLoaded', () => {
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', (e) => {
            const password = e.target.value;
            let strength = 0;
            let feedback = '';
            let color = '';
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (password.length === 0) {
                feedback = '';
            } else if (strength < 2) {
                feedback = '❌ Weak - Add more characters';
                color = '#c33';
            } else if (strength < 4) {
                feedback = '⚠️ Medium - Add numbers or symbols';
                color = '#f90';
            } else {
                feedback = '✅ Strong password';
                color = '#3c3';
            }
            
            const indicator = document.getElementById('newPasswordStrength');
            indicator.textContent = feedback;
            indicator.style.color = color;
        });
    }
});

// Change Password
async function changePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    document.getElementById('changePasswordError').classList.add('hidden');
    document.getElementById('changePasswordSuccess').classList.add('hidden');
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        document.getElementById('changePasswordError').textContent = 'All fields are required';
        document.getElementById('changePasswordError').classList.remove('hidden');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        document.getElementById('changePasswordError').textContent = 'New passwords do not match';
        document.getElementById('changePasswordError').classList.remove('hidden');
        return;
    }
    
    if (newPassword.length < 8) {
        document.getElementById('changePasswordError').textContent = 'New password must be at least 8 characters';
        document.getElementById('changePasswordError').classList.remove('hidden');
        return;
    }
    
    try {
        const response = await fetch('/justchat/api/change-password.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('changePasswordSuccess').textContent = 'Password changed successfully!';
            document.getElementById('changePasswordSuccess').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('changePasswordModal').classList.remove('show');
            }, 2000);
        } else {
            document.getElementById('changePasswordError').textContent = data.error;
            document.getElementById('changePasswordError').classList.remove('hidden');
        }
    } catch (error) {
        document.getElementById('changePasswordError').textContent = 'Failed to change password';
        document.getElementById('changePasswordError').classList.remove('hidden');
    }
}

// Show Activity Logs
async function showActivityLogs() {
    document.getElementById('activityLogsModal').classList.add('show');
    
    try {
        const response = await fetch('/justchat/api/activity-logs.php');
        const data = await response.json();
        
        if (data.success) {
            displayActivityLogs(data.logs);
        }
    } catch (error) {
        document.getElementById('activityLogsList').innerHTML = 
            '<p style="text-align: center; color: #c33;">Failed to load activity logs</p>';
    }
}

// Display Activity Logs
function displayActivityLogs(logs) {
    const listDiv = document.getElementById('activityLogsList');
    
    if (logs.length === 0) {
        listDiv.innerHTML = '<p style="text-align: center; color: #999;">No activity logs found</p>';
        return;
    }
    
    listDiv.innerHTML = logs.map(log => {
        const date = new Date(log.created_at);
        const formattedDate = date.toLocaleString();
        
        let icon = '';
        let eventText = '';
        let color = '';
        
        switch(log.event_type) {
            case 'login_success':
                icon = '✅';
                eventText = 'Successful Login';
                color = '#3c3';
                break;
            case 'login_failure':
                icon = '❌';
                eventText = 'Failed Login Attempt';
                color = '#c33';
                break;
            case 'logout':
                icon = '🚪';
                eventText = 'Logout';
                color = '#666';
                break;
            case 'password_reset':
                icon = '🔒';
                eventText = 'Password Changed';
                color = '#f90';
                break;
            case 'account_locked':
                icon = '⚠️';
                eventText = 'Account Locked';
                color = '#c33';
                break;
            default:
                icon = '📝';
                eventText = log.event_type;
                color = '#666';
        }
        
        return `
            <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 24px;">${icon}</div>
                <div style="flex: 1;">
                    <div style="font-weight: bold; color: ${color};">${eventText}</div>
                    <div style="font-size: 13px; color: #666;">IP: ${log.ip_address}</div>
                    <div style="font-size: 12px; color: #999;">${formattedDate}</div>
                </div>
            </div>
        `;
    }).join('');
}
// View Friend Profile
async function viewFriendProfile(userId) {
    document.getElementById('viewProfileModal').classList.add('show');
    
    try {
        const response = await fetch(`/justchat/api/profile.php?user_id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            displayViewProfile(data.user, true); // true = is friend
        }
    } catch (error) {
        console.error('Failed to load profile', error);
    }
}

// View User Profile (from directory)
async function viewUserProfile(userId) {
    document.getElementById('viewProfileModal').classList.add('show');
    
    try {
        const response = await fetch(`/justchat/api/profile.php?user_id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            // Check if already friends
            const friendsResponse = await fetch('/justchat/api/friends.php');
            const friendsData = await friendsResponse.json();
            const isFriend = friendsData.success && 
                            friendsData.data.some(f => f.id == userId);
            
            displayViewProfile(data.user, isFriend);
        }
    } catch (error) {
        console.error('Failed to load profile', error);
    }
}

// Display profile in view modal
function displayViewProfile(user, isFriend) {
    // Avatar
    const initial = user.full_name.charAt(0).toUpperCase();
    const avatarEl = document.getElementById('viewProfileAvatar');
    
    if (user.profile_image_path) {
        avatarEl.style.backgroundImage = `url('/${user.profile_image_path}')`;
        avatarEl.style.backgroundSize = 'cover';
        avatarEl.style.backgroundPosition = 'center';
        avatarEl.textContent = '';
    } else {
        avatarEl.style.backgroundImage = 'none';
        avatarEl.textContent = initial;
    }
    
    // Profile info
    document.getElementById('viewProfileName').textContent = user.full_name;
    document.getElementById('viewProfileUsername').textContent = '@' + user.username;
    document.getElementById('viewProfileUniversity').textContent = user.university || 'Not set';
    document.getElementById('viewProfileMajor').textContent = user.major || 'Not set';
    document.getElementById('viewProfileYear').textContent = user.year || 'Not set';
    document.getElementById('viewProfilePronouns').textContent = user.pronouns || 'Not set';
    document.getElementById('viewProfileNickname').textContent = user.nickname || 'Not set';
    document.getElementById('viewProfileBio').textContent = user.bio || 'No bio yet';
    document.getElementById('viewProfileHobbies').textContent = user.hobbies || 'No hobbies listed';
    
    const statusText = user.availability_status || 'Available';
    document.getElementById('viewProfileStatus').textContent = '🟢 ' + statusText;
    
    // Action buttons
    const actionsDiv = document.getElementById('viewProfileActions');
    if (isFriend) {
        actionsDiv.innerHTML = `
            <button onclick="openChat(${user.id}, '${user.full_name}')" style="width: auto; padding: 12px 24px;">
                💬 Send Message
            </button>
        `;
    } else {
        actionsDiv.innerHTML = `
            <button onclick="sendFriendRequest(${user.id}, '${user.full_name}'); document.getElementById('viewProfileModal').classList.remove('show');" 
                    style="width: auto; padding: 12px 24px;">
                👋 Add Friend
            </button>
        `;
    }
}