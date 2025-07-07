document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search');
    const clearButton = document.querySelector('.clear-button');
    const categoryButtons = document.querySelectorAll('.category-btn');
    const recipesContainer = document.getElementById('recipes-container');
    const recipeCards = recipesContainer.querySelectorAll('.recipe-card'); // Mengambil semua kartu resep

    // Fungsi untuk memperbarui tampilan tombol clear pencarian
    function toggleClearButton(searchTerm) {
        if (searchTerm.length > 0) {
            clearButton.style.display = 'block';
        } else {
            clearButton.style.display = 'none';
        }
    }

    // Fungsi utama untuk memfilter resep
    function filterRecipes() {
        const searchTerm = searchInput.value.toLowerCase().trim(); // Ambil nilai pencarian
        const activeCategoryBtn = document.querySelector('.category-btn.active');
        const activeCategory = activeCategoryBtn ? activeCategoryBtn.dataset.category : 'all'; // Ambil kategori aktif

        let visibleCardsCount = 0;

        recipeCards.forEach(card => {
            // Parsing data resep dari atribut data-recipe
            const recipeData = JSON.parse(card.dataset.recipe); 

            // Mengambil data untuk pencarian dan filter
            const name = (recipeData.name || '').toLowerCase();
            const description = (recipeData.description || '').toLowerCase();
            const category = (recipeData.category || '').toLowerCase();
            // Gabungkan bahan-bahan dan langkah-langkah menjadi string untuk pencarian
            const ingredients = (recipeData.ingredients && Array.isArray(recipeData.ingredients) ? recipeData.ingredients.map(item => item.toLowerCase()).join(' ') : '');
            const steps = (recipeData.steps && Array.isArray(recipeData.steps) ? recipeData.steps.map(item => item.toLowerCase()).join(' ') : '');

            // Logika filter berdasarkan kategori
            const matchesCategory = (activeCategory === 'all' || category === activeCategory);

            // Logika filter berdasarkan pencarian
            const matchesSearch = searchTerm === '' ||
                                  name.includes(searchTerm) ||
                                  description.includes(searchTerm) ||
                                  category.includes(searchTerm) ||
                                  ingredients.includes(searchTerm) ||
                                  steps.includes(searchTerm);

            if (matchesCategory && matchesSearch) {
                card.style.display = 'block'; // Tampilkan kartu
                visibleCardsCount++;
            } else {
                card.style.display = 'none'; // Sembunyikan kartu
            }
        });

        // Menampilkan atau menyembunyikan pesan "Tidak ada resep ditemukan"
        const noResultsDiv = document.querySelector('.no-results');
        if (visibleCardsCount === 0) {
            if (!noResultsDiv) {
                const div = document.createElement('div');
                div.classList.add('no-results');
                div.innerHTML = '<i class="fas fa-utensils"></i><p>Tidak ada resep yang ditemukan.</p>';
                recipesContainer.appendChild(div);
            } else {
                noResultsDiv.style.display = 'block';
            }
        } else {
            if (noResultsDiv) {
                noResultsDiv.style.display = 'none';
            }
        }
    }

    // Event listener untuk input pencarian
    searchInput.addEventListener('input', function() { // Gunakan 'input' untuk real-time filtering
        filterRecipes();
        toggleClearButton(searchInput.value.toLowerCase().trim());
    });

    // Event listener untuk tombol clear pencarian
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        filterRecipes(); // Reset filter
        toggleClearButton('');
    });

    // Event listeners untuk tombol kategori
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Hapus kelas 'active' dari semua tombol kategori
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            // Tambahkan kelas 'active' ke tombol yang diklik
            this.classList.add('active');
            filterRecipes(); // Terapkan filter kategori
        });
    });

    // Fungsi untuk mengarahkan ke halaman detail resep
    window.viewRecipeDetails = function(recipeId) {
        window.location.href = `<?= base_url('recipe/') ?>${recipeId}`;
    };

    // Fungsi untuk mengarahkan ke halaman riwayat pengguna
    window.goToRiwayat = function() {
        // Mengambil username dari elemen span di navbar
        const usernameSpan = document.querySelector('.user-profile .username');
        const username = usernameSpan ? usernameSpan.textContent.trim() : 'Anonim';
        window.location.href = `<?= base_url('riwayat/') ?>${encodeURIComponent(username)}`;
    };

    

// Add this to your existing script.js
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality for users page
    const searchInput = document.querySelector('.search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const keyword = this.value.toLowerCase();
            const userCards = document.querySelectorAll('.user-card');
            
            userCards.forEach(card => {
                const username = card.querySelector('h3').textContent.toLowerCase();
                if (username.includes(keyword)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide clear button
            const clearButton = document.querySelector('.clear-button');
            clearButton.style.display = keyword.length > 0 ? 'block' : 'none';
        });
    }
});

// Keep existing functions
window.clearSearch = function() {
    const searchInput = document.querySelector('.search');
    if (searchInput) {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        document.querySelector('.clear-button').style.display = 'none';
        searchInput.focus();
    }
};

window.goToRiwayat = function() {
    const username = document.querySelector('.username')?.textContent || 'Anonim';
    window.location.href = `${baseUrl}riwayat/${encodeURIComponent(username)}`;
};


document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search');
    const clearButton = document.querySelector('.clear-button');
    const userCards = document.querySelectorAll('.user-card');

    if (searchInput && clearButton && userCards) {
        // Show/hide clear button based on input value
        searchInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                clearButton.style.display = 'block';
            } else {
                clearButton.style.display = 'none';
            }
            filterUsers(); // Call filter function on input
        });

        // Clear search input
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            clearButton.style.display = 'none';
            filterUsers(); // Show all users after clearing
        });

        // Filter users based on search input
        function filterUsers() {
            const searchTerm = searchInput.value.toLowerCase();
            userCards.forEach(card => {
                const usernameElement = card.querySelector('.user-info h3');
                const emailElement = card.querySelector('.user-info .user-email');
                if (usernameElement && emailElement) {
                    const username = usernameElement.textContent.toLowerCase();
                    const email = emailElement.textContent.toLowerCase(); // Check email too

                    if (username.includes(searchTerm) || email.includes(searchTerm)) {
                        card.style.display = 'flex'; // Show card
                    } else {
                        card.style.display = 'none'; // Hide card
                    }
                }
            });
        }
    }
});

// Function for "Riwayat" button in navbar (example, adjust as needed)
function goToRiwayat() {
    // You might want to redirect to a general history page
    // or if the user is logged in, to their own history.
    // For now, let's just go to the main history page if available.
    window.location.href = '<?= base_url("riwayat") ?>'; // Adjust this URL
}



    // Inisialisasi awal saat halaman dimuat
    filterRecipes(); // Terapkan filter awal (semua resep)
    toggleClearButton(searchInput.value.toLowerCase().trim()); // Sembunyikan tombol clear jika tidak ada input
});