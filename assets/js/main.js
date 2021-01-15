
/*
    Clear the flash part of a page before Turbolinks caches it
 */
document.addEventListener("turbolinks:before-cache", function() {
    document.getElementById("flash").innerHTML = "";
});
