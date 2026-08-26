// Ouverture/fermeture du menu mobile — utilisé sur toutes les pages du site.
const navToggle=document.getElementById('navToggle');
const navLinks=document.getElementById('navLinks');
navToggle.addEventListener('click',()=>{const o=navLinks.classList.toggle('open');navToggle.setAttribute('aria-expanded',o);});
navLinks.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{navLinks.classList.remove('open');navToggle.setAttribute('aria-expanded',false);}));
