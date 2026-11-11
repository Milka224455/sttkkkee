(function(){
  var root=document.getElementById('bh');if(!root)return;
  var fr=document.documentElement.lang==='fr',loc=fr?'fr-FR':'de-DE';
  var T=fr?{empty:'Aucun bonus pour l’instant. Ajoute ton premier bonus ci-dessus.',del:'Supprimer',confirm:'Effacer tout le hunt ?',ph:'Gain',
            hunt:'Phase 1 · Hunting : ajoute tes bonus',open:'Phase 2 · Opening : entre le gain de chaque bonus',done:'Hunt terminé',
            next:'Prochain bonus à ouvrir',need:'Il faut au moins un bonus pour lancer l’opening.',fin:'RTP final',bal:'Bilan final',
            res:'Résultat',win:'Hunt gagnant',lose:'Hunt perdant'}
          :{empty:'Noch keine Boni. Füge oben deinen ersten Bonus hinzu.',del:'Löschen',confirm:'Den ganzen Hunt löschen?',ph:'Gewinn',
            hunt:'Phase 1 · Hunting: Boni hinzufügen',open:'Phase 2 · Opening: Gewinn jedes Bonus eintragen',done:'Hunt beendet',
            next:'Nächster Bonus',need:'Füge mindestens einen Bonus hinzu, um das Opening zu starten.',fin:'Finaler RTP',bal:'Finale Bilanz',
            res:'Ergebnis',win:'Hunt im Plus',lose:'Hunt im Minus'};
  var KEY='sb20-bonushunt-v2';
  var st={start:100,rows:[],phase:'hunt'};
  try{var s=JSON.parse(localStorage.getItem(KEY)||'null');if(s&&Array.isArray(s.rows))st=s}catch(e){}
  function save(){try{localStorage.setItem(KEY,JSON.stringify(st))}catch(e){}}
  var $=function(id){return document.getElementById(id)};
  var eur=function(v){return new Intl.NumberFormat(loc,{style:'currency',currency:'EUR'}).format(v)};
  var x=function(v){return isFinite(v)?v.toFixed(2).replace('.',',')+'x':'–'};
  var num=function(v){return parseFloat(String(v).replace(',','.'))};
  var has=function(r){return r.win!==''&&r.win!=null&&isFinite(num(r.win))};
  var esc=function(s){return String(s).replace(/[&<>"]/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]})};
  $('bh-start').value=st.start;

  function be(){var sb=st.rows.reduce(function(a,r){return a+r.bet},0);return sb?st.start/sb:NaN}
  function render(){
    var opening=st.phase!=='hunt',allDone=st.rows.length&&st.rows.every(has);
    if(opening&&allDone)st.phase='done'; else if(opening)st.phase='open';
    root.dataset.phase=st.phase;
    $('bh-phase').textContent=st.phase==='hunt'?T.hunt:st.phase==='open'?T.open:'✓ '+T.done;
    $('bh-start').disabled=opening;
    var tb=$('bh-rows'),nextI=st.rows.findIndex(function(r){return !has(r)});
    if(!st.rows.length){tb.innerHTML='<tr><td colspan="5" class="bh-empty">'+T.empty+'</td></tr>'}
    else tb.innerHTML=st.rows.map(function(r,i){
      var w=has(r),m=w?num(r.win)/r.bet:NaN;
      var cell=opening?'<input class="bh-win" data-i="'+i+'" inputmode="decimal" placeholder="'+T.ph+'" value="'+(w?esc(r.win):'')+'">':'<span class="small">–</span>';
      var del=opening?'':' <button class="bh-del" data-i="'+i+'" aria-label="'+T.del+'">×</button>';
      return '<tr'+(opening&&i===nextI?' class="bh-next"':'')+'><td>'+(i+1)+'</td><td>'+esc(r.slot)+'</td><td>'+eur(r.bet)+'</td><td>'+cell+'</td>'+
        '<td><span class="bh-x '+(w?(m>=be()?'up':'down'):'')+'">'+(w?x(m):'–')+'</span>'+del+'</td></tr>';
    }).join('');
    stats(nextI);save();
  }
  function stats(nextI){
    var start=st.start,sumBet=0,won=0,open=0,openBet=0,xs=0;
    st.rows.forEach(function(r){sumBet+=r.bet;if(has(r)){var w=num(r.win);won+=w;xs+=w/r.bet;open++}else openBet+=r.bet});
    var n=st.rows.length;
    $('s-n').textContent=st.phase==='hunt'?n:open+' / '+n;
    $('s-avgbet').textContent=n?eur(sumBet/n):'–';
    $('s-be').textContent=n?x(start/sumBet):'–';
    $('s-cbe').textContent=st.phase==='hunt'?'–':(openBet?(start-won<=0?'0x ✓':x((start-won)/openBet)):'–');
    $('s-won').textContent=eur(won);
    $('s-avgx').textContent=open?x(xs/open):'–';
    var rtp=start>0?won/start*100:0;
    $('s-rtp').textContent=st.phase==='hunt'?'–':rtp.toFixed(1).replace('.',',')+' %';
    $('s-rtp').style.color=st.phase!=='hunt'&&rtp>=100?'var(--green)':'';
    var bal=won-start;$('s-bal').textContent=st.phase==='hunt'?'–':(bal>=0?'+':'')+eur(bal);
    $('s-bal').style.color=st.phase==='hunt'?'':bal>=0?'var(--green)':'var(--red)';
    $('s-bar').style.width=(st.phase==='hunt'?0:Math.min(100,rtp))+'%';
    var res=$('bh-result');
    if(st.phase==='done'){
      res.hidden=false;res.className='bh-result '+(bal>=0?'win':'lose');
      res.innerHTML='<small>'+T.res+' · '+(bal>=0?T.win:T.lose)+'</small><div><span>'+T.fin+'<b>'+rtp.toFixed(1).replace('.',',')+' %</b></span><span>'+T.bal+'<b>'+(bal>=0?'+':'')+eur(bal)+'</b></span><span>Ø X<b>'+x(xs/open)+'</b></span></div>';
    } else res.hidden=true;
    if(st.phase==='open'&&nextI>-1){var el=document.querySelector('.bh-win[data-i="'+nextI+'"]');if(el&&document.activeElement===document.body)el.focus({preventScroll:true})}
  }
  $('bh-add').addEventListener('submit',function(e){
    e.preventDefault();var slot=$('bh-slot').value.trim()||('Bonus '+(st.rows.length+1)),bet=num($('bh-bet').value);
    if(!(bet>0)){$('bh-bet').focus();return}
    st.rows.push({slot:slot.slice(0,60),bet:bet,win:''});render();$('bh-slot').value='';$('bh-slot').focus();
  });
  $('bh-start').addEventListener('input',function(){var v=num(this.value);st.start=v>0?v:0;render()});
  $('bh-go').addEventListener('click',function(){if(!st.rows.length){alert(T.need);return}st.phase='open';render();var f=document.querySelector('.bh-win');if(f)f.focus()});
  $('bh-back').addEventListener('click',function(){st.phase='hunt';render()});
  $('bh-rows').addEventListener('change',function(e){if(e.target.classList.contains('bh-win')){st.rows[+e.target.dataset.i].win=e.target.value.trim();render();
    var n=document.querySelector('.bh-next .bh-win');if(n)n.focus()}});
  $('bh-rows').addEventListener('keydown',function(e){if(e.key==='Enter'&&e.target.classList.contains('bh-win')){e.target.blur()}});
  $('bh-rows').addEventListener('click',function(e){if(e.target.classList.contains('bh-del')){st.rows.splice(+e.target.dataset.i,1);render()}});
  $('bh-reset').addEventListener('click',function(){if(confirm(T.confirm)){st={start:st.start,rows:[],phase:'hunt'};render()}});
  render();
})();
