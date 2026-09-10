<?php
/*
Template Name: Catálogo
*/
get_header(); ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="catalogo-app">
  <div class="cat-header">
    <a href="<?php echo home_url(); ?>" class="logo">
      <svg viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="#1a3c6e"/><path d="M8 14h4v10H8zM14 10h4v14h-4zM20 6h4v18h-4z" fill="#C9A227"/></svg>
      REI DO AP&Ecirc;
    </a>
    <div class="cat-header-actions">
      <a href="<?php echo home_url(); ?>">Voltar ao Site</a>
    </div>
  </div>

  <div class="cat-search-bar">
    <input type="text" id="search-input" placeholder="Buscar por nome, bairro ou cidade...">
    <div class="cat-filter-group">
      <select id="filter-state"><option value="">Todos os estados</option></select>
      <select id="filter-bedrooms">
        <option value="">Qualquer dorm.</option>
        <option value="1">1 dorm.</option>
        <option value="2">2 dorm.</option>
        <option value="3">3 dorm.</option>
        <option value="4">4+ dorm.</option>
      </select>
    </div>
  </div>

  <div class="cat-source-tabs" id="source-tabs">
    <button class="cat-source-tab active" data-source="orulo">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
      Orulo
    </button>
    <button class="cat-source-tab" data-source="superbid">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Superbid
    </button>
  </div>

  <div class="cat-subcategories" id="subcategories" style="display:none">
    <div>
      <button class="cat-subcat-btn active" data-category="imoveis">Im&oacute;veis</button>
      <button class="cat-subcat-btn" data-category="carros">Carros & Motos</button>
      <button class="cat-subcat-btn" data-category="caminhoes">Caminh&otilde;es & &Ocirc;nibus</button>
      <button class="cat-subcat-btn" data-category="maquinas-agricolas">M&aacute;quinas Pesadas</button>
      <button class="cat-subcat-btn" data-category="transporte">Transporte</button>
      <button class="cat-subcat-btn" data-category="industrial">Industrial</button>
      <button class="cat-subcat-btn" data-category="animais">Animais</button>
      <button class="cat-subcat-btn" data-category="tecnologia">Tecnologia</button>
      <button class="cat-subcat-btn" data-category="moveis">M&oacute;veis</button>
      <button class="cat-subcat-btn" data-category="joias">Joias</button>
      <button class="cat-subcat-btn" data-category="sucatas">Sucatas</button>
    </div>
  </div>

  <div class="cat-app-body" id="app-body">
    <div class="cat-list-panel">
      <div class="cat-scroll-content">
        <div class="cat-results-info" id="results-info">Carregando...</div>
        <div class="cat-cards-container" id="cards-container"></div>
      </div>
      <div class="cat-pagination" id="pagination"></div>
    </div>
    <div class="cat-map-panel">
      <div id="map"></div>
    </div>
  </div>
</div>

<script>
var REI_DO_APE_API = '<?php echo rest_url('rei-do-ape/v1/'); ?>';
</script>
<script src="<?php echo get_template_directory_uri(); ?>/js/catalogo.js"></script>

<?php get_footer(); ?>
