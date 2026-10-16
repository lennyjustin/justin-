<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Simple, transparent pricing';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero compact-hero pricing-hero"><div class="eyebrow"><span class="eyebrow-dot"></span> PRICING TO EXPLORE</div><h1>Start free. Build what people need.</h1><p>Construction Estimator is an early pilot. The ideas below are proposed plans for future development—not active subscriptions or payment offers.</p></section>
<section class="pricing-grid">
    <article class="price-card"><span class="step-kicker">FOR EVERYONE</span><h2>Free</h2><div class="price-amount">KSh 0 <small>/ forever</small></div><p>Get started with early-stage project estimates.</p><ul><li>Basic estimates & printable summaries</li><li>Sample material rates</li><li>Save estimates with a free account</li></ul><a class="button button-outline button-full" href="<?= e(url('estimate.php')) ?>">Start estimating</a></article>
    <article class="price-card price-featured"><span class="price-tag">FUTURE IDEA</span><span class="step-kicker">FOR INDEPENDENT BUILDERS</span><h2>Contractor</h2><div class="price-amount">KSh 500–1,500 <small>/ month, proposed</small></div><p>Potential tools for contractors who manage more projects.</p><ul><li>Reusable quotations & rate history</li><li>Client sharing</li><li>Branded reports (planned)</li></ul><span class="price-placeholder">Not available yet · No payment collected</span></article>
    <article class="price-card"><span class="step-kicker">FOR SMALL TEAMS</span><h2>Team</h2><div class="price-amount">KSh 2,000–5,000 <small>/ month, proposed</small></div><p>A future option for growing construction teams.</p><ul><li>Shared projects & multiple users</li><li>Company-branded reports</li><li>Team workflows (planned)</li></ul><span class="price-placeholder">Not available yet · No payment collected</span></article>
</section>
<section class="pricing-note"><strong>These are validation hypotheses.</strong> Proposed prices are not confirmed market averages. Payments, subscriptions and M-Pesa processing are not implemented. We recommend validating demand with local builders before investing in billing.</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
