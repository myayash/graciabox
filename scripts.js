let openDropdown = null;

function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    const button = dropdown.previousElementSibling; // Assuming the button is the previous sibling

    if (openDropdown && openDropdown !== dropdown) {
        openDropdown.style.display = 'none';
    }

    if (window.getComputedStyle(dropdown).display === 'none') {
        dropdown.style.display = 'block';
        openDropdown = dropdown;
    } else {
        dropdown.style.display = 'none';
        openDropdown = null;
    }
}

document.addEventListener('click', function(event) {
    if (openDropdown) {
        const button = openDropdown.previousElementSibling;
        if (!openDropdown.contains(event.target) && !button.contains(event.target)) {
            openDropdown.style.display = 'none';
            openDropdown = null;
        }
    }
});