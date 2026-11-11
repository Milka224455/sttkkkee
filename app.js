// Mobile-Menü
document.addEventListener('click',function(e){
  var b=e.target.closest('.burger');
  if(b){var n=document.querySelector('.nav');var o=n.classList.toggle('open');b.setAttribute('aria-expanded',o)}
});
