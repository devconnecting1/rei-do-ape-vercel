(function(){
  var apiBase = window.REI_DO_APE_API || '/wp-json/rei-do-ape/v1/';
  var params = new URLSearchParams(window.location.search);
  var id = params.get('id');
  var main = document.getElementById('main');

  if (!id) { showError('ID inv\u00E1lido.'); return; }

  var isSuperbid = id.indexOf('sb-') === 0;
  var apiUrl = isSuperbid ? apiBase + 'superbid-detail?id=' + encodeURIComponent(id.replace('sb-',''))
    : apiBase + 'orulo-detail?id=' + encodeURIComponent(id);

  fetch(apiUrl)
    .then(function(r){ return r.json(); })
    .then(function(b){
      if (b.error) { showError(b.error); return; }
      if (isSuperbid) renderSuperbid(b);
      else render(b);
    })
    .catch(function(e){ showError('Erro ao carregar: ' + e.message); });

  function showError(msg) {
    main.innerHTML = '<div style="text-align:center;padding:80px 20px"><h2>Erro</h2><p style="color:#888;margin:8px 0">' + esc(msg) + '</p><a href="/catalogo/" class="det-cta-btn det-cta-outline" style="display:inline-block;width:auto;padding:12px 32px">Voltar ao Cat\u00E1logo</a></div>';
  }

  function render(b) {
    var imgs = [];
    if (b.images) {
      b.images.forEach(function(img){
        if (typeof img === 'string') { imgs.push(img); return; }
        var url = img['1024x1024'] || img['520x280'] || img['200x140'] || '';
        if (url) imgs.push(url);
      });
    }
    if (!imgs.length && b.default_image) {
      var url = b.default_image['1024x1024'] || b.default_image['520x280'] || '';
      if (url) imgs.push(url);
    }

    var address = '';
    if (b.address) {
      var parts = [];
      if (b.address.street) parts.push(b.address.street + (b.address.number ? ', ' + b.address.number : ''));
      if (b.address.area) parts.push(b.address.area);
      if (b.address.city) parts.push(b.address.city);
      if (b.address.state) parts.push(b.address.state);
      address = parts.join(', ');
    }

    var html = '';

    if (imgs.length) {
      html += '<div class="det-gallery" id="gallery">';
      html += '<div class="det-gallery-main" id="gallery-main">';
      html += '<img id="gallery-img" src="' + esc(imgs[0]) + '" alt="' + esc(b.name) + '">';
      if (imgs.length > 1) {
        html += '<button class="det-gallery-nav prev" onclick="galleryNav(-1)">&#10094;</button>';
        html += '<button class="det-gallery-nav next" onclick="galleryNav(1)">&#10095;</button>';
      }
      html += '<div class="det-gallery-count" id="gallery-count">1/' + imgs.length + '</div>';
      html += '</div>';
      html += '<div class="det-gallery-thumbs" id="gallery-thumbs">';
      for (var i = 0; i < imgs.length; i++) {
        html += '<img src="' + esc(imgs[i]) + '" data-idx="' + i + '"' + (i === 0 ? ' class="active"' : '') + ' onclick="galleryGo(' + i + ')">';
      }
      html += '</div></div>';
    }

    html += '<div class="det-content-grid">';
    html += '<div>';
    html += '<div class="det-info-section">';
    html += '<h1>' + esc(b.name || 'Empreendimento') + '</h1>';
    if (address) html += '<div class="det-address">' + esc(address) + '</div>';
    if (b.developer) html += '<div class="det-developer">' + esc(b.developer.name) + '</div>';
    if (b.status) html += '<span class="det-status-badge">' + esc(b.status) + '</span>';
    html += '<div class="det-specs-grid">';
    if (b.min_bedrooms || b.max_bedrooms) {
      var beds = b.min_bedrooms === b.max_bedrooms ? b.min_bedrooms : (b.min_bedrooms || '?') + ' a ' + (b.max_bedrooms || '?');
      html += '<div class="det-spec-item"><div class="det-spec-value">' + beds + '</div><div class="det-spec-label">Dormit\u00F3rios</div></div>';
    }
    if (b.min_area || b.max_area) {
      var area = b.min_area === b.max_area ? b.min_area : (b.min_area || '?') + ' a ' + (b.max_area || '?');
      html += '<div class="det-spec-item"><div class="det-spec-value">' + area + '</div><div class="det-spec-label">\u00C1rea (m\u00B2)</div></div>';
    }
    if (b.stock) html += '<div class="det-spec-item"><div class="det-spec-value">' + b.stock + '</div><div class="det-spec-label">Unidades</div></div>';
    html += '</div></div>';

    var features = b.features || [];
    if (features.length) {
      html += '<div class="det-features-section"><h2>Caracter\u00EDsticas</h2><div class="det-features-grid">';
      features.forEach(function(f){
        html += '<div class="det-feature-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>' + esc(f) + '</div>';
      });
      html += '</div></div>';
    }

    if (b.description) {
      html += '<div class="det-desc-section"><h2>Descri\u00E7\u00E3o</h2><p>' + esc(b.description) + '</p></div>';
    }
    html += '</div>';

    html += '<div class="det-sidebar">';
    html += '<div class="det-sidebar-card">';
    html += '<div class="det-price-label">A partir de</div>';
    html += '<div class="det-price-value">' + brl(b.min_price) + '</div>';
    html += '</div>';
    if (b.orulo_url) {
      html += '<a class="det-cta-btn det-cta-primary" href="' + esc(b.orulo_url) + '" target="_blank" rel="noopener">Ver no Orulo</a>';
    }
    html += '<a class="det-cta-btn det-cta-outline" href="/catalogo/">Voltar ao Cat\u00E1logo</a>';
    html += '</div>';
    html += '</div>';

    main.innerHTML = html;
    document.title = (b.name || 'Empreendimento') + ' \u2014 Rei do Ap\u00EA';
    setupGallery(imgs);
  }

  function renderSuperbid(b) {
    var imgs = b.photos || [];
    var addr = b.location || {};
    var auction = b.auction || {};
    var props = b.properties || {};
    var seller = b.seller || {};

    var html = '';

    html += '<div class="det-content-grid">';
    html += '<div>';

    if (imgs.length) {
      html += '<div class="det-gallery" id="gallery">';
      html += '<div class="det-gallery-main" id="gallery-main">';
      html += '<img id="gallery-img" src="' + esc(imgs[0]) + '" alt="' + esc(b.title) + '">';
      if (imgs.length > 1) {
        html += '<button class="det-gallery-nav prev" onclick="galleryNav(-1)">&#10094;</button>';
        html += '<button class="det-gallery-nav next" onclick="galleryNav(1)">&#10095;</button>';
      }
      html += '<div class="det-gallery-count" id="gallery-count">1/' + imgs.length + '</div>';
      html += '</div>';
      html += '<div class="det-gallery-thumbs" id="gallery-thumbs">';
      for (var i = 0; i < imgs.length; i++) {
        html += '<img src="' + esc(imgs[i]) + '" data-idx="' + i + '"' + (i === 0 ? ' class="active"' : '') + ' onclick="galleryGo(' + i + ')">';
      }
      html += '</div></div>';
    }

    html += '<div class="det-info-section">';
    html += '<h1>' + esc(b.title || 'Lote ' + b.lotNumber) + '</h1>';
    if (seller.name) html += '<div class="det-developer">' + esc(seller.name) + '</div>';
    html += '<div class="det-specs-grid">';
    if (props.areatotal) html += '<div class="det-spec-item"><div class="det-spec-value">' + esc(props.areatotal) + ' m\u00B2</div><div class="det-spec-label">\u00C1rea Total</div></div>';
    if (props.quartos) html += '<div class="det-spec-item"><div class="det-spec-value">' + esc(props.quartos) + '</div><div class="det-spec-label">Dormit\u00F3rios</div></div>';
    if (props.vagas) html += '<div class="det-spec-item"><div class="det-spec-value">' + esc(props.vagas) + '</div><div class="det-spec-label">Vagas</div></div>';
    html += '</div></div>';

    if (b.description) {
      html += '<div class="det-desc-section"><h2>Descri\u00E7\u00E3o</h2><div style="font-size:14px;line-height:1.7;color:#444">' + b.description + '</div></div>';
    }

    html += '</div>';

    html += '<div class="det-sidebar">';
    html += '<div class="det-sidebar-card">';
    html += '<div class="det-price-label">' + (b.bids && b.bids.totalBids ? '\u00DAltimo lance' : 'Lance') + '</div>';
    html += '<div class="det-price-value">' + brl(b.price) + '</div>';
    if (b.directSaleValue && b.directSaleValue !== b.price) html += '<div style="color:#1a3c6e;font-size:13px;font-weight:600;margin-top:4px">Venda direta: ' + brl(b.directSaleValue) + '</div>';
    html += '</div>';
    html += '<a class="det-cta-btn det-cta-primary" href="' + esc(b.url) + '" target="_blank" rel="noopener" style="background:#9333ea">HABILITE-SE</a>';
    html += '<a class="det-cta-btn det-cta-outline" href="/catalogo/">Voltar ao Cat\u00E1logo</a>';
    html += '</div>';
    html += '</div>';

    main.innerHTML = html;
    document.title = (b.title || 'Lote') + ' \u2014 Rei do Ap\u00EA';
    setupGallery(imgs);
  }

  function setupGallery(imgs) {
    window.galleryGo = function(idx) {
      var img = document.getElementById('gallery-img');
      var thumbs = document.querySelectorAll('.det-gallery-thumbs img');
      img.src = imgs[idx];
      document.getElementById('gallery-count').textContent = (idx + 1) + '/' + imgs.length;
      for (var t = 0; t < thumbs.length; t++) thumbs[t].classList.toggle('active', t === idx);
      window._galleryIdx = idx;
    };
    window._galleryIdx = 0;
    window.galleryNav = function(dir) {
      var next = (window._galleryIdx + dir + imgs.length) % imgs.length;
      window.galleryGo(next);
    };
    var mainImg = document.getElementById('gallery-main');
    if (mainImg) {
      mainImg.addEventListener('click', function(e) {
        if (e.target.classList.contains('det-gallery-nav')) return;
        var img = document.getElementById('gallery-img');
        img.classList.toggle('zoomed');
        mainImg.style.cursor = img.classList.contains('zoomed') ? 'zoom-out' : 'zoom-in';
      });
    }
  }

  function brl(v){ return v ? 'R$ ' + Number(v).toLocaleString('pt-BR',{minimumFractionDigits:0,maximumFractionDigits:0}) : 'Consulte'; }
  function esc(s){ var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
})();
