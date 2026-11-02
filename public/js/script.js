// Si un logo ne se charge pas (le club n'en a pas), on remplace l'image
// par un rond avec les initiales du club, au lieu de l'icône "image cassée".
document.addEventListener('error', function (evenement) {
    var image = evenement.target;
    if (image.tagName === 'IMG' && image.hasAttribute('data-initiales')) {
        var rond = document.createElement('span');
        rond.className = image.className + ' initiales';
        rond.textContent = image.getAttribute('data-initiales');
        image.replaceWith(rond);
    }
}, true); // true : l'erreur d'une image ne "remonte" pas, il faut l'écouter en phase de capture
