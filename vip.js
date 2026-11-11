(function(){
  var lang=document.documentElement.lang==='fr'?'fr':'de';
  var T={
    de:{loc:'de-DE',none:'Noch kein Level',max:'Höchstes Level erreicht',day:'Tage',na:'Ø Tagesumsatz eingeben',lt:'weniger als 1 Tag',silver:'Silber',plat:'Platin'},
    fr:{loc:'fr-FR',none:'Pas encore de niveau',max:'Niveau maximum atteint',day:'jours',na:'Indique ta mise moyenne/jour',lt:'moins d’1 jour',silver:'Silver',plat:'Platinum'}
  }[lang];
  var tiers=[
    ['Bronze',1e4],[T.silver,5e4],['Gold',1e5],
    [T.plat+' I',2.5e5],[T.plat+' II',5e5],[T.plat+' III',1e6],
    [T.plat+' IV',2.5e6],[T.plat+' V',5e6],[T.plat+' VI',1e7],
    ['Diamond I',2.5e7],['Diamond II',5e7],['Diamond III',1e8],
    ['Diamond IV',2.5e8],['Diamond V',5e8]
  ];
  var fmt=function(n){return new Intl.NumberFormat(T.loc,{maximumFractionDigits:0}).format(n)+' €'};
  var tb=document.getElementById('tiers-table');
  if(tb){tb.innerHTML=tiers.map(function(t,i){var nx=tiers[i+1];return '<tr><td>'+t[0]+'</td><td>'+fmt(t[1])+'</td><td>'+(nx?fmt(nx[1]-t[1]):'–')+'</td></tr>'}).join('')}
  var w=document.getElementById('wager');
  if(!w)return;
  var $=function(id){return document.getElementById(id)};
  function calc(){
    var v=Math.max(0,parseFloat(w.value)||0);
    var ci=-1;for(var i=0;i<tiers.length;i++){if(v>=tiers[i][1])ci=i}
    var cur=ci>=0?tiers[ci]:null,nxt=tiers[ci+1];
    $('r-cur').textContent=cur?cur[0]:T.none;
    if(!nxt){$('r-pct').textContent='100 %';$('r-bar').style.width='100%';$('r-left').textContent=T.max;return}
    var base=cur?cur[1]:0,span=nxt[1]-base,pct=Math.min(100,(v-base)/span*100),left=nxt[1]-v;
    $('r-pct').textContent=pct.toFixed(1).replace('.',',')+' %';
    $('r-bar').style.width=pct+'%';
    $('r-left').textContent=fmt(left)+' → '+nxt[0];
  }
  w.addEventListener('input',calc);calc();
})();
