<?php get_header(); ?>

<main class="wrap" style="padding:60px 24px">
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <article>
      <h1><?php the_title(); ?></h1>
      <div><?php the_content(); ?></div>
    </article>
  <?php endwhile; else : ?>
    <p>Nenhum conte&uacute;do encontrado.</p>
  <?php endif; ?>
</main>

<?php get_footer(); ?>
