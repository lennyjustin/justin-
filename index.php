<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-grid">
        <div class="hero-copy">
            <div class="eyebrow"><span class="eyebrow-dot"></span> CONSTRUCTION PLANNING, MADE CLEARER</div>
            <h1>Understand your build <span>before work begins.</span></h1>
            <p class="hero-lead">Construction Estimator helps homeowners, fundis and small contractors create an early construction budget. Enter basic project details to see estimated materials, labour, transport and contingency in Kenyan shillings.</p>
            <div class="hero-actions">
                <a class="button button-large" href="<?= e(url('estimate.php')) ?>">Build a project estimate <span aria-hidden="true">↗</span></a>
                <a class="text-link" href="#what-it-does">See what it does <span aria-hidden="true">↓</span></a>
            </div>
            <div class="hero-points"><span>✓ Simple project inputs</span><span>✓ KSh breakdown</span><span>✓ Clear assumptions</span></div>
        </div>
        <div class="estimate-preview" aria-label="Example construction estimate">
            <div class="preview-top"><span class="preview-icon">⌂</span><span class="preview-status">PRELIMINARY ESTIMATE</span><span class="preview-dots">•••</span></div>
            <p class="preview-label">3-bedroom family home</p><p class="preview-location">↗ Kiambu County · 120 m²</p>
            <div class="preview-total"><span>Estimated project total</span><strong>KSh 6,840,000</strong><small>Example only · Not a supplier quote</small></div>
            <div class="preview-breakdown">
                <div><span><i class="dot dot-sage"></i> Materials</span><strong>KSh 3,910,000</strong></div>
                <div><span><i class="dot dot-blue"></i> Labour</span><strong>KSh 1,760,000</strong></div>
                <div><span><i class="dot dot-gold"></i> Transport & contingency</span><strong>KSh 1,170,000</strong></div>
            </div>
            <div class="preview-footer"><span class="mini-check">✓</span> Adjust rates to suit your area</div>
        </div>
    </div>
    <div class="hero-stats"><div><strong>7</strong><span>core materials estimated</span></div><div><strong>3</strong><span>finish levels to compare</span></div><div><strong>KSh</strong><span>clear local pricing</span></div><div class="stat-disclaimer">A practical first pass—not a substitute for a professional BOQ.</div></div>
</section>
<section class="section features-section" id="what-it-does">
    <div class="section-heading"><div class="eyebrow">WHAT CONSTRUCTION ESTIMATOR DOES</div><h2>From project details<br><span>to a cost breakdown.</span></h2><p>This website turns a few early project details into a simple planning estimate, so you can understand the main costs to investigate before construction begins.</p></div>
    <div class="feature-grid">
        <article class="feature-card"><span class="feature-icon icon-green">01</span><h3>Describe your project</h3><p>Choose a new home, extension or rental project. Add location, floor area, number of floors and a basic, standard or premium finish level.</p><a href="<?= e(url('estimate.php')) ?>">Enter project details <span>↗</span></a></article>
        <article class="feature-card"><span class="feature-icon icon-blue">02</span><h3>Understand likely costs</h3><p>Review indicative quantities and costs for cement, sand, ballast, blocks, steel, roofing and paint, alongside labour, transport and contingency allowances.</p><a href="<?= e(url('estimate.php')) ?>">See the cost calculator <span>↗</span></a></article>
        <article class="feature-card"><span class="feature-icon icon-gold">03</span><h3>Adapt, save and share</h3><p>Use sample prices or set your own supplier rates. Print a project summary or create an account to save estimates for later.</p><a href="<?= e(url('register.php')) ?>">Save your project <span>↗</span></a></article>
    </div>
</section>
<section class="section showcase-section" id="sample-projects">
    <div class="section-heading">
        <div class="eyebrow">SAMPLE BUILDING IDEAS</div>
        <h2>Explore projects<br><span>and indicative prices.</span></h2>
        <p>Explore the supplied premium building concepts, with their approximate floor areas and indicative budget ranges.</p>
    </div>
    <div class="showcase-grid">
        <article class="showcase-card">
            <div class="showcase-art showcase-home">
                <img src="<?= e(url('assets/img/family-home-concept.png')) ?>" alt="Five-room family home concept poster showing the house, interiors, approximate size and indicative budget">
                <span class="showcase-tag">5-ROOM FAMILY HOME</span>
            </div>
            <div class="showcase-content">
                <div class="showcase-title"><h3>5-room family home</h3><span>160–220 m²</span></div>
                <p>A spacious home concept with five bedrooms, living areas, kitchen and veranda.</p>
                <div class="showcase-price"><span>Indicative concept budget range</span><strong>KSh 6M–10M</strong></div>
                <a class="showcase-link" href="<?= e(url('estimate.php')) ?>">Create a preliminary estimate <span aria-hidden="true">↗</span></a>
            </div>
        </article>
        <article class="showcase-card">
            <div class="showcase-art showcase-church">
                <img src="<?= e(url('assets/img/church-concept.png')) ?>" alt="Three-floor church concept poster showing the building, sanctuary, meeting rooms, approximate size and indicative budget">
                <span class="showcase-tag">3-FLOOR CHURCH</span>
            </div>
            <div class="showcase-content">
                <div class="showcase-title"><h3>Community church</h3><span>700–1,200 m²</span></div>
                <p>A multi-floor church concept with a main sanctuary, choir area, meeting rooms and classrooms.</p>
                <div class="showcase-price"><span>Indicative concept budget range</span><strong>KSh 18M–30M</strong></div>
                <a class="showcase-link" href="<?= e(url('estimate.php')) ?>">Create a preliminary estimate <span aria-hidden="true">↗</span></a>
            </div>
        </article>
        <article class="showcase-card">
            <div class="showcase-art showcase-rental">
                <img src="<?= e(url('assets/img/apartment-concept.png')) ?>" alt="Apartment building concept poster showing the building, interiors, approximate size and indicative budget">
                <span class="showcase-tag">APARTMENT BUILDING</span>
            </div>
            <div class="showcase-content">
                <div class="showcase-title"><h3>Rental apartment building</h3><span>800–3,000 m²</span></div>
                <p>A multi-unit residential concept with shared parking and landscaped outdoor space.</p>
                <div class="showcase-price"><span>Indicative concept budget range</span><strong>KSh 25M–80M</strong></div>
                <a class="showcase-link" href="<?= e(url('estimate.php')) ?>">Create a preliminary estimate <span aria-hidden="true">↗</span></a>
            </div>
        </article>
    </div>
    <p class="showcase-disclaimer">The images and budget ranges are concept guides from the supplied posters, not live project listings, verified market prices or contractor quotes. The estimator creates a separate preliminary calculation using simplified sample rates, so its result can differ substantially. Confirm designs, specifications and current local prices with qualified professionals and suppliers.</p>
</section>
<section class="section how-section" id="how-it-works">
    <div class="how-copy"><div class="eyebrow">HOW TO USE THE PROJECT</div><h2>A simple guide<br>to your first estimate.</h2><p>Use the estimator to explore an early budget, then update the sample material rates with prices from local suppliers. The result is intended to support planning and conversations—not replace professional advice or a detailed quantity survey.</p><a class="button" href="<?= e(url('estimate.php')) ?>">Open the estimator <span>↗</span></a></div>
    <div class="steps">
        <div class="step"><span>01</span><div><h3>Enter the project scope</h3><p>Choose a project type, add the county, area per floor and number of floors, then select a finish level.</p></div></div>
        <div class="step"><span>02</span><div><h3>Check each cost allowance</h3><p>See estimated material quantities and prices, plus labour, transport and a contingency amount.</p></div></div>
        <div class="step"><span>03</span><div><h3>Use it as a planning guide</h3><p>Print the breakdown, compare it with supplier prices, or sign in to save it and manage your own rates.</p></div></div>
    </div>
</section>
<section class="disclaimer-band"><div><span class="disclaimer-mark">i</span><p><strong>Important:</strong> This is an early-stage planning estimate using demonstration assumptions and sample prices. It is not a bill of quantities, structural design or supplier quotation. Confirm quantities and prices with qualified professionals and local suppliers.</p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
