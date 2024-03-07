export function showOverlay() {
    document.getElementById("overlay").classList.remove("hidden");
    document.body.style.overflow = "hidden";
}

export function hideOverlay() {
    document.getElementById("overlay").classList.add("hidden");
    document.body.style.overflow = "auto";
}
