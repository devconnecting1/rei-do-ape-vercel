(function(){
  var apiBase = window.REI_DO_APE_API || '/wp-json/rei-do-ape/v1/';
  var currentPage = 1;
  var perPage = 10;
  var totalPages = 1;
  var searchTimer = null;
  var currentSource = 'orulo';
  var currentCategory = 'imoveis';
  var currentRequestId = 0;

  var statesList = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
  var stateSelect = document.getElementById('filter-state');
  statesList.forEach(function(s){
    var opt = document.createElement('option');
    opt.value = s; opt.textContent = s;
    stateSelect.appendChild(opt);
  });

  document.querySelectorAll('.cat-source-tab').forEach(function(tab){
    tab.addEventListener('click', function(){
      document.querySelectorAll('.cat-source-tab').forEach(function(t){ t.classList.remove('active'); });
      tab.classList.add('active');
      currentSource = tab.dataset.source;
      currentPage = 1;
      var body = document.getElementById('app-body');
      var subcats = document.getElementById('subcategories');
      if (currentSource === 'orulo') { body.classList.remove('no-map'); loadMapMarkers(); subcats.style.display = 'none'; }
      else if (currentSource === 'superbid') { body.classList.add('no-map'); markers.clearLayers(); subcats.style.display = 'block'; currentCategory = 'imoveis'; document.querySelectorAll('.cat-subcat-btn').forEach(function(b){ b.classList.toggle('active', b.dataset.category === 'imoveis'); }); }
      else { body.classList.add('no-map'); markers.clearLayers(); subcats.style.display = 'none'; }
      showSkeletons();
      loadBuildings();
    });
  });

  document.querySelectorAll('.cat-subcat-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.cat-subcat-btn').forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      currentCategory = btn.dataset.category;
      currentPage = 1;
      showSkeletons();
      loadBuildings();
    });
  });

  document.getElementById('search-input').addEventListener('input', function(){
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function(){ currentPage = 1; loadBuildings(); if (currentSource === 'orulo') loadMapMarkers(); }, 400);
  });
  document.getElementById('filter-state').addEventListener('change', function(){ currentPage = 1; loadBuildings(); if (currentSource === 'orulo') loadMapMarkers(); });
  document.getElementById('filter-bedrooms').addEventListener('change', function(){ currentPage = 1; loadBuildings(); if (currentSource === 'orulo') loadMapMarkers(); });

  var map = L.map('map',{zoomControl:false}).setView([-15.78,-47.93],4);
  L.control.zoom({position:'topright'}).addTo(map);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(map);
  var markers = L.featureGroup().addTo(map);
  var markerIcon = L.divIcon({className:'',iconSize:[28,40],iconAnchor:[14,40],popupAnchor:[0,-36],html:'<svg width="28" height="40" viewBox="0 0 28 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 0C6.268 0 0 6.268 0 14c0 10.5 14 26 14 26s14-15.5 14-26C28 6.268 21.732 0 14 0z" fill="#1a3c6e"/><circle cx="14" cy="14" r="6" fill="#fff"/></svg>'});

  var mapLoading = document.createElement('div');
  mapLoading.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(255,255,255,.9);padding:12px 20px;border-radius:8px;font-size:13px;color:#666;z-index:1000;display:none;box-shadow:0 2px 8px rgba(0,0,0,.1)';
  mapLoading.textContent = 'Carregando mapa...';
  document.getElementById('map').appendChild(mapLoading);

  showSkeletons();
  loadBuildings();
  loadMapMarkers();

  function loadMapMarkers(){
    mapLoading.style.display = 'block';
    var st = document.getElementById('filter-state').value;
    var br = document.getElementById('filter-bedrooms').value;
    var q = document.getElementById('search-input').value.trim();
    var params = new URLSearchParams();
    params.set('total_pages', '10');
    if (st) params.set('state', st);
    if (br) params.set('bedrooms', br);
    if (q) params.set('q', q);
    var reqId = currentRequestId;
    fetch(apiBase + 'orulo-map?' + params.toString())
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (reqId !== currentRequestId) return;
        mapLoading.style.display = 'none';
        markers.clearLayers();
        (data.markers || []).forEach(function(m){
          var marker = L.marker([m.lat, m.lng], {icon: markerIcon});
          var popup = '<div style="min-width:180px"><strong>' + esc(m.name) + '</strong><br>R$ ' + Number(m.price).toLocaleString('pt-BR',{maximumFractionDigits:0}) + '<br><a href="' + getDetalheUrl(m.id) + '" style="color:#1a3c6e;font-weight:600">Ver detalhes &rarr;</a></div>';
          marker.bindPopup(popup);
          markers.addLayer(marker);
        });
        try { if (markers.getLayers().length) map.fitBounds(markers.getBounds().pad(0.1)); } catch(e) {}
      })
      .catch(function(){ mapLoading.style.display = 'none'; });
  }

  function loadBuildings(){
    currentRequestId++;
    var reqId = currentRequestId;
    var container = document.getElementById('cards-container');
    var st = document.getElementById('filter-state').value;
    var br = document.getElementById('filter-bedrooms').value;
    var q = document.getElementById('search-input').value.trim();

    if (currentSource === 'superbid') {
      var params = new URLSearchParams();
      params.set('category', currentCategory);
      params.set('page', currentPage);
      params.set('pageSize', '10');
      fetch(apiBase + 'superbid?' + params.toString())
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (reqId !== currentRequestId) return;
          if (data.error) { document.getElementById('results-info').textContent = 'Erro: ' + data.error; return; }
          var items = data.items || [];
          var total = data.total || items.length;
          totalPages = Math.ceil(total / 10);
          document.getElementById('results-info').innerHTML = '<strong>' + total.toLocaleString('pt-BR') + '</strong> itens Superbid encontrados';
          renderSuperbidCards(items);
          renderPagination();
        })
        .catch(function(e){ if (reqId !== currentRequestId) return; document.getElementById('results-info').textContent = 'Erro: ' + e.message; });
      return;
    }

    var params = new URLSearchParams();
    params.set('page', currentPage);
    params.set('results_per_page', perPage);
    if (st) params.set('state', st);
    if (br) params.set('bedrooms', br);
    if (q) params.set('q', q);

    fetch(apiBase + 'orulo?' + params.toString())
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (reqId !== currentRequestId) return;
        if (data.error) { document.getElementById('results-info').textContent = 'Erro: ' + data.error; return; }
        var buildings = data.buildings || [];
        var total = data.total || 0;
        totalPages = data.total_pages || 1;
        document.getElementById('results-info').innerHTML = '<strong>' + total.toLocaleString('pt-BR') + '</strong> empreendimentos encontrados';
        renderCards(buildings);
        renderPagination();
      })
      .catch(function(e){
        if (reqId !== currentRequestId) return;
        document.getElementById('results-info').textContent = 'Erro ao carregar: ' + e.message;
      });
  }

  function renderCards(buildings){
    var container = document.getElementById('cards-container');
    if (!buildings.length){
      container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#888"><h3 style="margin-bottom:8px;color:#1a1a1a">Nenhum resultado</h3><p>Tente ajustar os filtros.</p></div>';
      return;
    }
    var cols = window.innerWidth > 1100 ? 2 : 1;
    container.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
    var html = '';
    buildings.forEach(function(b){
      var img = b.default_image ? (b.default_image['520x280'] || b.default_image['200x140'] || '') : '';
      var address = formatAddress(b.address);
      var specs = [];
      if (b.min_bedrooms || b.max_bedrooms) {
        var beds = b.min_bedrooms === b.max_bedrooms ? b.min_bedrooms : (b.min_bedrooms || '?') + ' a ' + (b.max_bedrooms || '?');
        specs.push(beds + ' dorm.');
      }
      if (b.min_area || b.max_area) {
        var area = b.min_area === b.max_area ? b.min_area : (b.min_area || '?') + ' a ' + (b.max_area || '?');
        specs.push(area + ' m\u00B2');
      }
      if (b.stock) specs.push(b.stock + ' un.');
      var portfolio = b.portfolio || [];
      var badgeClass = 'cat-badge';
      var badgeText = b.status || '';
      if (portfolio.indexOf('Lan\u00E7amento') !== -1) { badgeClass = 'cat-badge cat-badge-launch'; badgeText = 'Lan\u00E7amento'; }
      else if (b.opportunity && (b.opportunity.featured_building || b.opportunity.tenanted_investment_property)) { badgeClass = 'cat-badge cat-badge-opportunity'; badgeText = 'Oportunidade'; }

      var features = (b.features || []).slice(0, 4);

      html += '<div class="cat-card" onclick="window.location.href=\'' + getDetalheUrl(b.id) + '\'">';
      html += '<div class="cat-card-img">';
      if (img) html += '<img src="' + esc(img) + '" alt="' + esc(b.name) + '" loading="lazy">';
      if (badgeText) html += '<div class="' + badgeClass + '">' + esc(badgeText) + '</div>';
      html += '</div>';
      html += '<div class="cat-card-body">';
      html += '<div class="cat-card-name">' + esc(b.name) + '</div>';
      html += '<div class="cat-card-address">' + esc(address) + '</div>';
      if (b.developer) html += '<div class="cat-card-developer">' + esc(b.developer.name) + '</div>';
      html += '<div class="cat-card-divider"></div>';
      html += '<div class="cat-card-price"><div class="label">A partir de</div><div class="value">' + brl(b.min_price) + '</div></div>';
      html += '<div class="cat-card-specs">';
      specs.forEach(function(s){ html += '<span class="spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>' + s + '</span>'; });
      html += '</div>';
      if (features.length) {
        html += '<div class="cat-card-features">';
        features.forEach(function(f){ html += '<span class="feat">' + esc(f) + '</span>'; });
        html += '</div>';
      }
      html += '</div></div>';
    });
    container.innerHTML = html;
  }

  function renderSuperbidCards(items){
    var container = document.getElementById('cards-container');
    if (!items.length){
      container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#888"><h3 style="margin-bottom:8px;color:#1a1a1a">Nenhum resultado</h3><p>Tente ajustar os filtros.</p></div>';
      return;
    }
    var cols = window.innerWidth > 1100 ? 2 : 1;
    container.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
    var html = '';
    items.forEach(function(b){
      html += '<div class="cat-card" onclick="window.location.href=\'' + getDetalheUrl(b.id) + '\'">';
      html += '<div class="cat-card-img">';
      if (b.thumbnail) {
        html += '<img src="' + esc(b.thumbnail) + '" alt="" loading="lazy">';
      } else {
        html += '<div style="width:100%;height:100%;background:linear-gradient(135deg,#4a0080,#7b2fbe);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;text-align:center;padding:8px">Superbid</div>';
      }
      html += '<div class="cat-badge" style="background:rgba(75,0,130,.85)">Superbid</div>';
      if (b.sold) html += '<div class="cat-badge" style="top:auto;bottom:8px;background:rgba(180,0,0,.9)">Vendido</div>';
      else if (b.reserved) html += '<div class="cat-badge" style="top:auto;bottom:8px;background:rgba(200,150,0,.9)">Reservado</div>';
      html += '</div>';
      html += '<div class="cat-card-body">';
      html += '<div class="cat-card-name">' + esc(b.title || 'Lote ' + b.lotNumber) + '</div>';
      html += '<div class="cat-card-developer">' + esc(b.store || b.auctioneer || 'Superbid') + '</div>';
      html += '<div class="cat-card-divider"></div>';
      html += '<div class="cat-card-price"><div class="label">Lance</div><div class="value">' + brl(b.price) + '</div></div>';
      if (b.cutValue && b.cutValue !== b.price) html += '<div style="font-size:12px;color:#888;margin-top:2px">Corte: ' + brl(b.cutValue) + '</div>';
      html += '<div class="cat-card-specs">';
      if (b.photoCount) html += '<span class="spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>' + b.photoCount + ' fotos</span>';
      if (b.endDate) html += '<span class="spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>' + esc(b.endDate.split(' ')[0]) + '</span>';
      html += '</div></div></div>';
    });
    container.innerHTML = html;
  }

  function renderPagination(){
    var el = document.getElementById('pagination');
    if (totalPages <= 1) { el.innerHTML = ''; return; }
    var html = '';
    html += '<button ' + (currentPage <= 1 ? 'disabled' : '') + ' onclick="goPage(' + (currentPage - 1) + ')">&lsaquo; Ant.</button>';
    var start = Math.max(1, currentPage - 2);
    var end = Math.min(totalPages, currentPage + 2);
    if (start > 1) html += '<button onclick="goPage(1)">1</button>';
    if (start > 2) html += '<span class="page-info">...</span>';
    for (var i = start; i <= end; i++){
      html += '<button class="' + (i === currentPage ? 'active' : '') + '" onclick="goPage(' + i + ')">' + i + '</button>';
    }
    if (end < totalPages - 1) html += '<span class="page-info">...</span>';
    if (end < totalPages) html += '<button onclick="goPage(' + totalPages + ')">' + totalPages + '</button>';
    html += '<button ' + (currentPage >= totalPages ? 'disabled' : '') + ' onclick="goPage(' + (currentPage + 1) + ')">Pr\u00F3x. &rsaquo;</button>';
    el.innerHTML = html;
  }

  window.goPage = function(p){
    currentPage = p;
    document.querySelector('.cat-scroll-content').scrollTop = 0;
    showSkeletons();
    loadBuildings();
    if (currentSource === 'orulo') loadMapMarkers();
  };

  function showSkeletons(){
    var c = document.getElementById('cards-container');
    var cols = window.innerWidth > 1100 ? 2 : 1;
    c.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
    var html = '';
    for (var i = 0; i < 6; i++){
      html += '<div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-body">';
      html += '<div class="skeleton-line w75"></div><div class="skeleton-line w50"></div><div class="skeleton-line w33"></div>';
      html += '<div style="height:8px"></div>';
      html += '<div class="skeleton-line w50"></div><div class="skeleton-line w75"></div>';
      html += '</div></div>';
    }
    c.innerHTML = html;
  }

  function getDetalheUrl(id) {
    return '/detalhe/?id=' + encodeURIComponent(id);
  }

  function formatAddress(a){
    if (!a) return '';
    var parts = [];
    if (a.street) parts.push(a.street + (a.number ? ', ' + a.number : ''));
    if (a.area) parts.push(a.area);
    if (a.city) parts.push(a.city);
    if (a.state) parts.push(a.state);
    return parts.join(' \u2014 ');
  }
  function brl(v){ return v ? 'R$ ' + Number(v).toLocaleString('pt-BR',{minimumFractionDigits:0,maximumFractionDigits:0}) : 'Consulte'; }
  function esc(s){ var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
})();
