<?php
/*
Template Name: Detalhe
*/
get_header(); ?>

<div class="detalhe-app">
  <div class="det-header">
    <a href="<?php echo home_url(); ?>" class="logo">
      <svg viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="#1a3c6e"/><path d="M8 14h4v10H8zM14 10h4v14h-4zM20 6h4v18h-4z" fill="#C9A227"/></svg>
      REI DO AP&Ecirc;
    </a>
    <a href="<?php echo home_url('/catalogo'); ?>" class="det-back-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Voltar ao Cat&aacute;logo
    </a>
  </div>

  <div class="det-main" id="main">
    <div class="det-loading" id="loading">
      <div class="det-spinner"></div>
      <div>Carregando im&oacute;vel...</div>
    </div>
  </div>

  <div class="det-footer">Rei do Ap&ecirc; &mdash; Morar Bem</div>
</div>

<script>
var REI_DO_APE_API = '<?php echo rest_url('rei-do-ape/v1/'); ?>';
</script>
<script src="<?php echo get_template_directory_uri(); ?>/js/detalhe.js"></script>

<?php get_footer(); ?>
