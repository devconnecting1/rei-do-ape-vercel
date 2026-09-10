<?php get_header(); ?>

<style>
<?php echo file_get_contents(get_template_directory() . '/css/theme.css'); ?>
</style>

<!-- TOP BAR -->
<div class="topbar">
  <div class="wrap">
    <div class="phones">
      <a href="tel:+5521999999999">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
        (21) 99999-9999
      </a>
    </div>
    <div class="place">
      Rio de Janeiro — RJ
    </div>
  </div>
</div>

<!-- HEADER -->
<header class="site-header">
  <div class="wrap">
    <a href="<?php echo home_url(); ?>" class="brand">
      <svg class="brand-mark" viewBox="0 0 38 38" fill="none"><rect width="38" height="38" rx="10" fill="#0A1830"/><path d="M9 16h5v12H9zM15 11h5v17h-5zM21 6h5v22h-5z" fill="#C9A227"/></svg>
      <div class="brand-name">
        <b>REI DO AP&Ecirc;</b>
        <small>MORAR BEM</small>
      </div>
    </a>

    <nav class="nav">
      <ul class="nav-list">
        <li class="nav-item">
          <a href="<?php echo home_url(); ?>" class="nav-link">Home</a>
        </li>
        <li class="nav-item">
          <a href="<?php echo home_url('/catalogo'); ?>" class="nav-link">Cat&aacute;logo</a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">Servi&ccedil;os</a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">Contato</a>
        </li>
      </ul>
    </nav>

    <div class="header-actions">
      <a href="https://wa.me/5521999999999" class="btn btn-gold cta-full" target="_blank">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
        Fale Conosco
      </a>
    </div>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="wrap hero-inner">
    <div class="hero-cols">
      <div class="hero-top">
        <span class="eyebrow hero-eyebrow">PORTAL IMOBILI&Aacute;RIO</span>
        <h1>Encontre o <em>im&oacute;vel ideal</em> com as melhores condi&ccedil;&otilde;es</h1>
        <p class="hero-sub">Leil&otilde;es da Caixa, venda direta, an&uacute;ncio do propriet&aacute;rio sem corretor e muito mais.</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
          <a href="<?php echo home_url('/catalogo'); ?>" class="btn btn-gold">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Ver Cat&aacute;logo
          </a>
          <a href="https://wa.me/5521999999999" class="btn btn-ghost-light" target="_blank">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
            WhatsApp
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST -->
<section class="trust">
  <div class="wrap">
    <ul>
      <li>
        <b>+2.000 Im&oacute;veis</b>
        <span>Caixa, Orulo e Superbid</span>
      </li>
      <li>
        <b>Atendimento Personalizado</b>
        <span>Assessoria dedicada</span>
      </li>
      <li>
        <b>Transpar&ecirc;ncia Total</b>
        <span>Documentos verificados</span>
      </li>
      <li>
        <b>Financiamento</b>
        <span>FGTS e Cr&eacute;dito imobili&aacute;rio</span>
      </li>
    </ul>
  </div>
</section>

<!-- SERVICES -->
<section class="section">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">NOSSOS SERVI&Ccedil;OS</span>
      <h2>Solu&ccedil;&otilde;es completas para voc&ecirc;</h2>
      <p>Oferecemos assessoria completa em todas as etapas do processo imobili&aacute;rio.</p>
    </div>
    <div class="svc-grid">
      <div class="svc">
        <div class="svc-ic">
          <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
        </div>
        <span class="tagline">LEIL&Atilde;O</span>
        <h3>Leil&otilde;es Caixa</h3>
        <p>Im&oacute;veis da Caixa Econ&ocirc;mica Federal com desconto de at&eacute; 30%. Financiamento direto com a Caixa.</p>
      </div>
      <div class="svc">
        <div class="svc-ic">
          <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        </div>
        <span class="tagline">VENDA DIRETA</span>
        <h3>Empreendimentos</h3>
        <p>Lan&ccedil;amentos e empreendimentos selecionados dos principais incorporadores.</p>
      </div>
      <div class="svc">
        <div class="svc-ic">
          <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <span class="tagline">SUPERBID</span>
        <h3>Leil&otilde;es Superbid</h3>
        <p>Leil&otilde;es online de im&oacute;veis, ve&iacute;culos e equipamentos com os melhores pre&ccedil;os.</p>
      </div>
      <div class="svc">
        <div class="svc-ic">
          <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <span class="tagline">ASSESSORIA</span>
        <h3>Regulariza&ccedil;&atilde;o</h3>
        <p>Regulariza&ccedil;&atilde;o de im&oacute;veis, matr&iacute;cula, certid&otilde;es e documenta&ccedil;&atilde;o completa.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="consult">
  <div class="wrap">
    <h2>Pronto para encontrar seu im&oacute;vel?</h2>
    <p>Fale com nossos especialistas e encontre a melhor oportunidade para voc&ecirc;.</p>
    <a href="https://wa.me/5521999999999" class="btn btn-gold" target="_blank">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
      Fale com um Especialista
    </a>
  </div>
</section>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="wrap">
    <div class="foot-main">
      <div class="foot-grid">
        <div class="foot-brand">
          <a href="<?php echo home_url(); ?>" class="brand">
            <div class="brand-name">
              <b>REI DO AP&Ecirc;</b>
              <small>MORAR BEM</small>
            </div>
          </a>
          <p class="foot-pitch">Portal imobili&aacute;rio completo: leil&otilde;es, venda direta, assessoria e servi&ccedil;os imobili&aacute;rios.</p>
        </div>
        <div class="foot-col">
          <h4>NAVEGA&Ccedil;&Atilde;O</h4>
          <ul>
            <li><a href="<?php echo home_url(); ?>">Home</a></li>
            <li><a href="<?php echo home_url('/catalogo'); ?>">Cat&aacute;logo</a></li>
          </ul>
        </div>
        <div class="foot-col">
          <h4>SERVI&Ccedil;OS</h4>
          <ul>
            <li><a href="#">Leil&otilde;es Caixa</a></li>
            <li><a href="#">Venda Direta</a></li>
            <li><a href="#">Superbid</a></li>
            <li><a href="#">Regulariza&ccedil;&atilde;o</a></li>
          </ul>
        </div>
        <div class="foot-col">
          <h4>CONTATO</h4>
          <ul>
            <li><a href="tel:+5521999999999">(21) 99999-9999</a></li>
            <li><a href="mailto:contato@reidoape.com.br">contato@reidoape.com.br</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="foot-legal">
      <span>&copy; <?php echo date('Y'); ?> Rei do Ap&ecirc;. Todos os direitos reservados.</span>
    </div>
  </div>
</footer>

<!-- WHATSAPP FLUTUANTE -->
<a href="https://wa.me/5521999999999" class="wa-float" target="_blank">
  <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
  WhatsApp
</a>

<?php get_footer(); ?>
