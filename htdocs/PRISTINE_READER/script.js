function filterData() {
    var location = document.getElementById('location').value;
    var dataType = document.getElementById('dataType').value;
    window.location.href = 'viewer.php?location=' + location + '&dataType=' + dataType;
}