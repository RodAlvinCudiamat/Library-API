const apiUrl = 'http://localhost/libraryapi/public'; // Change this to your actual API URL

// Function to set token in cookies securely
function setTokenInCookies(token) {
    document.cookie = `token=${token}; path=/; secure; SameSite=Strict`;
}

// Function to get token from cookies
function getTokenFromCookies() {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; token=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null; // Return null if no token is found
}

// Function to handle API responses that may contain a new token
function handleApiResponse(response) {
    return response.json().then(data => {
        if (data.token) {
            setTokenInCookies(data.token); // Store the new token in cookies
            delete data.token; // Remove token from data before returning
        }
        return data;
    });
}

// Utility function to handle API requests with token validation
async function apiRequest(url, method, body = null) {
    const token = getTokenFromCookies(); // Get the token before making the API call
    if (!token) {
        window.location.href = 'login.html'; // Redirect to login page if no token
        return;
    }

    const headers = {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}` // Include token in the Authorization header
    };

    const response = await fetch(url, {
        method,
        headers,
        body: body ? JSON.stringify(body) : null
    });

    return await handleApiResponse(response); // Check and set token if present
}

// Function to create a table from data
function createTable(data) {
    const table = document.createElement('table');
    table.classList.add('data-table');

    // Create table header
    const headerRow = document.createElement('tr');
    Object.keys(data[0]).forEach(key => {
        const th = document.createElement('th');
        th.innerText = key.charAt(0).toUpperCase() + key.slice(1); // Capitalize first letter
        headerRow.appendChild(th);
    });
    table.appendChild(headerRow);

    // Create table rows
    data.forEach(item => {
        const row = document.createElement('tr');
        Object.values(item).forEach(value => {
            const td = document.createElement('td');
            td.innerText = value;
            row.appendChild(td);
        });
        table.appendChild(row);
    });

    return table;
}

// User Registration
document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('registerUsername').value;
    const password = document.getElementById('registerPassword').value;

    const response = await fetch(`${apiUrl}/user/register`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, password })
    });

    const data = await response.json();
    document.getElementById('registerMessage').innerText = data.status === 'success' ? 'Registration successful!' : 'Registration failed.';
});

// User Login
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;

    const response = await fetch(`${apiUrl}/user/authenticate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, password })
    });

    const data = await handleApiResponse(response); // Check and set token if present
    if (data.status === 'success') {
        window.location.href = 'home.html'; // Redirect to home page
    } else {
        document.getElementById('loginMessage').innerText = 'Login failed.';
    }
});

// Fetch Users
document.getElementById('fetchUsers')?.addEventListener('click', async () => {
    const data = await apiRequest(`${apiUrl}/users`, 'GET');
    const userList = document.getElementById('userList');
    userList.innerHTML = ''; // Clear previous content
    userList.appendChild(createTable(data)); // Append the new table
});


// Add Author
document.getElementById('addAuthorForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const authorName = document.getElementById('authorName').value;

    const data = await apiRequest(`${apiUrl}/authors`, 'POST', { name: authorName });
    document.getElementById('authorMessage').innerText = data.status === 'success' ? 'Author added successfully!' : 'Failed to add author.';
});

// Fetch Authors
document.getElementById('fetchAuthors')?.addEventListener('click', async () => {
    const data = await apiRequest(`${apiUrl}/authors`, 'GET');
    document.getElementById('authorList').innerText = JSON.stringify(data, null, 2);
});

// Add Book
document.getElementById('addBookForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const bookTitle = document.getElementById('bookTitle').value;

    const data = await apiRequest(`${apiUrl}/books`, 'POST', { title: bookTitle });
    document.getElementById('bookMessage').innerText = data.status === 'success' ? 'Book added successfully!' : 'Failed to add book.';
});

// Fetch Books
document.getElementById('fetchBooks')?.addEventListener('click', async () => {
    const data = await apiRequest(`${apiUrl}/books`, 'GET');
    document.getElementById('bookList').innerText = JSON.stringify(data, null, 2);
});

// Add Book-Author Association
document.getElementById('addBooksAuthorsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const bookId = document.getElementById('bookId').value;
    const authorId = document.getElementById('authorId').value;

    const data = await apiRequest(`${apiUrl}/books-authors`, 'POST', { bookId, authorId });
    document.getElementById('booksAuthorsMessage').innerText = data.status === 'success' ? 'Association added successfully!' : 'Failed to add association.';
});

// Fetch Book-Author Associations
document.getElementById('fetchBooksAuthors')?.addEventListener('click', async () => {
    const data = await apiRequest(`${apiUrl}/books-authors`, 'GET');
    document.getElementById('booksAuthorsList').innerText = JSON.stringify(data, null, 2);
});

// Logout
document.getElementById('logoutButton')?.addEventListener('click', () => {
    document.cookie = 'token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'; // Clear the token from cookies
    window.location.href = 'login.html'; // Redirect to login page
});
