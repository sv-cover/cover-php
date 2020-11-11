document.querySelectorAll('.dark-mode-switch .switch').forEach(function(switchItem) {
    switchItem.addEventListener('change', function(evt) {
        var state = this.checked ? 'dark' : 'light';
        document.cookie = 'cover_color_mode=' + state + '; expires=Fri, 31 Dec 9999 23:59:59 GMT; SameSite=Lax';
        window.location.reload(true);
    });
});
