function toggleDropdown(id) {
    var dropdown = document.getElementById(id);
    if (window.getComputedStyle(dropdown).display === 'none') {
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}