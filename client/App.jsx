import { useCallback, useEffect, useState } from 'react';
import { Link, Navigate, Route, Routes, useNavigate, useParams } from 'react-router-dom';
import { api, formatKes, updateCsrfToken } from './api.js';

const initialForm = {
  project_name: '',
  location: '',
  project_type: 'house',
  floor_area: '',
  floors: '1',
  finish_level: 'standard',
  transport_percent: '8',
  contingency_percent: '10',
};

function App() {
  const [user, setUser] = useState(null);
  const [ready, setReady] = useState(false);
  const [flash, setFlash] = useState(null);
  const [sessionError, setSessionError] = useState('');

  const refreshSession = useCallback(async () => {
    try {
      const session = await api('/session');
      updateCsrfToken(session.csrfToken);
      setUser(session.user);
      setSessionError('');
    } catch (error) {
      setSessionError(error.message);
    } finally {
      setReady(true);
    }
  }, []);

  useEffect(() => { refreshSession(); }, [refreshSession]);

  const showFlash = (message, type = 'success') => {
    setFlash({ message, type });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const signOut = async () => {
    try {
      await api('/auth/logout', { method: 'POST', body: JSON.stringify({}) });
      setUser(null);
      updateCsrfToken('');
      await refreshSession();
      showFlash('You have signed out.');
    } catch (error) {
      showFlash(error.message, 'error');
    }
  };

  if (!ready) return <div className="loading-state">Loading Construction Estimator…</div>;
  if (sessionError) return <div className="loading-state" role="alert">{sessionError}</div>;

  return (
    <>
      <Header user={user} onSignOut={signOut} />
      {flash && <div className={`flash flash-${flash.type}`} role="status">{flash.message}</div>}
      <main>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/estimate" element={<Estimator user={user} showFlash={showFlash} />} />
          <Route path="/pricing" element={<Pricing />} />
          <Route path="/login" element={user ? <Navigate to="/dashboard" replace /> : <AuthPage mode="login" onAuth={setUser} showFlash={showFlash} />} />
          <Route path="/register" element={user ? <Navigate to="/dashboard" replace /> : <AuthPage mode="register" onAuth={setUser} showFlash={showFlash} />} />
          <Route path="/dashboard" element={user ? <Dashboard user={user} /> : <Navigate to="/login" replace />} />
          <Route path="/rates" element={user ? <Rates showFlash={showFlash} /> : <Navigate to="/login" replace />} />
          <Route path="/estimates/:id" element={user ? <EstimateDetail /> : <Navigate to="/login" replace />} />
          <Route path="*" element={<NotFound />} />
        </Routes>
      </main>
      <Footer />
    </>
  );
}

function Header({ user, onSignOut }) {
  const [open, setOpen] = useState(false);
  const closeMenu = () => setOpen(false);
  return (
    <header className="site-header">
      <div className="nav-wrap">
        <Link className="brand" to="/" aria-label="Construction Estimator home" onClick={closeMenu}>
          <span className="brand-mark">C</span><span>Construction <span className="brand-light">Estimator</span></span>
        </Link>
        <button className="menu-toggle" type="button" aria-expanded={open} aria-controls="main-nav" onClick={() => setOpen(!open)}>Menu</button>
        <nav className={`main-nav${open ? ' nav-open' : ''}`} id="main-nav">
          <Link to="/" onClick={closeMenu}>Home</Link>
          <Link to="/estimate" onClick={closeMenu}>Estimator</Link>
          <Link to="/pricing" onClick={closeMenu}>Pricing</Link>
          {user ? <>
            <Link to="/dashboard" onClick={closeMenu}>My projects</Link>
            <Link to="/rates" onClick={closeMenu}>My rates</Link>
            <span className="nav-user">{user.name}</span>
            <button className="button button-small button-outline" type="button" onClick={() => { closeMenu(); onSignOut(); }}>Sign out</button>
          </> : <>
            <Link to="/login" onClick={closeMenu}>Sign in</Link>
            <Link className="button button-small" to="/register" onClick={closeMenu}>Get started <span aria-hidden="true">↗</span></Link>
          </>}
        </nav>
      </div>
    </header>
  );
}

function Footer() {
  return <footer className="site-footer"><div className="footer-inner">
    <Link className="brand brand-footer" to="/"><span className="brand-mark">C</span><span>Construction <span className="brand-light">Estimator</span></span></Link>
    <p>Understand your project. Plan your construction budget.</p>
    <span>Preliminary planning estimates only · Kenya-first</span>
  </div></footer>;
}

function Home() {
  return <>
    <section className="hero"><div className="hero-grid">
      <div className="hero-copy">
        <div className="eyebrow"><span className="eyebrow-dot" /> CONSTRUCTION PLANNING, MADE CLEARER</div>
        <h1>Understand your build <span>before work begins.</span></h1>
        <p className="hero-lead">Construction Estimator helps homeowners, fundis and small contractors create an early construction budget. Enter basic project details to see estimated materials, labour, transport and contingency in Kenyan shillings.</p>
        <div className="hero-actions"><Link className="button button-large" to="/estimate">Build a project estimate <span aria-hidden="true">↗</span></Link><a className="text-link" href="#what-it-does">See what it does <span aria-hidden="true">↓</span></a></div>
        <div className="hero-points"><span>✓ Simple project inputs</span><span>✓ KSh breakdown</span><span>✓ Clear assumptions</span></div>
      </div>
      <div className="estimate-preview" aria-label="Example construction estimate">
        <div className="preview-top"><span className="preview-icon">⌂</span><span className="preview-status">PRELIMINARY ESTIMATE</span><span className="preview-dots">•••</span></div>
        <p className="preview-label">3-bedroom family home</p><p className="preview-location">↗ Kiambu County · 120 m²</p>
        <div className="preview-total"><span>Estimated project total</span><strong>KSh 6,840,000</strong><small>Example only · Not a supplier quote</small></div>
        <div className="preview-breakdown"><div><span><i className="dot dot-sage" /> Materials</span><strong>KSh 3,910,000</strong></div><div><span><i className="dot dot-blue" /> Labour</span><strong>KSh 1,760,000</strong></div><div><span><i className="dot dot-gold" /> Transport & contingency</span><strong>KSh 1,170,000</strong></div></div>
        <div className="preview-footer"><span className="mini-check">✓</span> Adjust rates to suit your area</div>
      </div>
    </div><div className="hero-stats"><div><strong>7</strong><span>core materials estimated</span></div><div><strong>3</strong><span>finish levels to compare</span></div><div><strong>KSh</strong><span>clear local pricing</span></div><div className="stat-disclaimer">A practical first pass—not a substitute for a professional BOQ.</div></div></section>
    <section className="section features-section" id="what-it-does">
      <div className="section-heading"><div className="eyebrow">WHAT CONSTRUCTION ESTIMATOR DOES</div><h2>From project details<br /><span>to a cost breakdown.</span></h2><p>This website turns a few early project details into a simple planning estimate, so you can understand the main costs to investigate before construction begins.</p></div>
      <div className="feature-grid">
        <article className="feature-card"><span className="feature-icon icon-green">01</span><h3>Describe your project</h3><p>Choose a new home, extension or rental project. Add location, floor area, number of floors and a basic, standard or premium finish level.</p><Link to="/estimate">Enter project details <span>↗</span></Link></article>
        <article className="feature-card"><span className="feature-icon icon-blue">02</span><h3>Understand likely costs</h3><p>Review indicative quantities and costs for cement, sand, ballast, blocks, steel, roofing and paint, alongside labour, transport and contingency allowances.</p><Link to="/estimate">See the cost calculator <span>↗</span></Link></article>
        <article className="feature-card"><span className="feature-icon icon-gold">03</span><h3>Adapt, save and share</h3><p>Use sample prices or set your own supplier rates. Print a project summary or create an account to save estimates for later.</p><Link to="/register">Save your project <span>↗</span></Link></article>
      </div>
    </section>
    <section className="section showcase-section"><div className="section-heading"><div className="eyebrow">SAMPLE BUILDING IDEAS</div><h2>Explore projects<br /><span>and indicative prices.</span></h2><p>Explore the supplied premium building concepts, with their approximate floor areas and indicative budget ranges.</p></div>
      <div className="showcase-grid">
        <Showcase image="family-home-concept.png" alt="Five-room family home concept poster" tag="5-ROOM FAMILY HOME" title="5-room family home" area="160–220 m²" description="A spacious home concept with five bedrooms, living areas, kitchen and veranda." budget="KSh 6M–10M" />
        <Showcase image="church-concept.png" alt="Three-floor church concept poster" tag="3-FLOOR CHURCH" title="Community church" area="700–1,200 m²" description="A multi-floor church concept with a main sanctuary, choir area, meeting rooms and classrooms." budget="KSh 18M–30M" />
        <Showcase image="apartment-concept.png" alt="Apartment building concept poster" tag="APARTMENT BUILDING" title="Rental apartment building" area="800–3,000 m²" description="A multi-unit residential concept with shared parking and landscaped outdoor space." budget="KSh 25M–80M" />
      </div>
      <p className="showcase-disclaimer">The images and budget ranges are concept guides, not verified market prices or contractor quotes. Confirm designs, specifications and current local prices with qualified professionals and suppliers.</p>
    </section>
    <section className="section how-section"><div className="how-copy"><div className="eyebrow">HOW TO USE THE PROJECT</div><h2>A simple guide<br />to your first estimate.</h2><p>Use the estimator to explore an early budget, then update the sample material rates with prices from local suppliers. The result supports planning—not professional advice or a detailed quantity survey.</p><Link className="button" to="/estimate">Open the estimator <span>↗</span></Link></div>
      <div className="steps"><Step number="01" title="Enter the project scope">Choose a project type, add the county, area per floor and number of floors, then select a finish level.</Step><Step number="02" title="Check each cost allowance">See estimated material quantities and prices, plus labour, transport and a contingency amount.</Step><Step number="03" title="Use it as a planning guide">Print the breakdown, compare it with supplier prices, or sign in to save it and manage your rates.</Step></div>
    </section>
    <section className="disclaimer-band"><div><span className="disclaimer-mark">i</span><p><strong>Important:</strong> This is an early-stage planning estimate using demonstration assumptions and sample prices. It is not a bill of quantities, structural design or supplier quotation.</p></div></section>
  </>;
}

function Showcase({ image, alt, tag, title, area, description, budget }) {
  return <article className="showcase-card"><div className="showcase-art"><img src={`/img/${image}`} alt={alt} /><span className="showcase-tag">{tag}</span></div><div className="showcase-content"><div className="showcase-title"><h3>{title}</h3><span>{area}</span></div><p>{description}</p><div className="showcase-price"><span>Indicative concept budget range</span><strong>{budget}</strong></div><Link className="showcase-link" to="/estimate">Create a preliminary estimate <span>↗</span></Link></div></article>;
}

function Step({ number, title, children }) {
  return <div className="step"><span>{number}</span><div><h3>{title}</h3><p>{children}</p></div></div>;
}

function PageHero({ eyebrow, title, children, className = '' }) {
  return <section className={`page-hero compact-hero ${className}`}><div className="eyebrow"><span className="eyebrow-dot" /> {eyebrow}</div><h1>{title}</h1><p>{children}</p></section>;
}

function Estimator({ user, showFlash }) {
  const [form, setForm] = useState(initialForm);
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const change = (event) => setForm({ ...form, [event.target.name]: event.target.value });
  const calculate = async (event) => {
    event.preventDefault();
    setError('');
    setBusy(true);
    try {
      setResult(await api('/estimates/preview', { method: 'POST', body: JSON.stringify(form) }));
      setTimeout(() => document.getElementById('estimate-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
    } catch (failure) {
      setError(failure.message);
      setResult(null);
    } finally {
      setBusy(false);
    }
  };
  const save = async () => {
    setError('');
    try {
      const saved = await api('/estimates', { method: 'POST', body: JSON.stringify(form) });
      showFlash('Your estimate has been saved.');
      window.location.assign(`/estimates/${saved.id}`);
    } catch (failure) {
      setError(failure.message);
    }
  };
  return <>
    <PageHero eyebrow="YOUR PROJECT STARTS HERE" title="Construction cost estimator">Get a preliminary, itemized budget in KSh. Adjust the assumptions to reflect your project.</PageHero>
    <section className="estimator-layout"><div className="form-card">
      <div className="form-card-head"><div><span className="step-kicker">PROJECT DETAILS</span><h2>Tell us what you’re building</h2></div><span className="form-number">01</span></div>
      {error && <div className="form-error" role="alert">{error}</div>}
      <form className="estimate-form" onSubmit={calculate}>
        <label>Project name <span className="optional">Optional</span><input name="project_name" maxLength="100" placeholder="e.g. Our family home" value={form.project_name} onChange={change} /></label>
        <div className="form-row"><label>Project type<select name="project_type" value={form.project_type} onChange={change}><option value="house">New home</option><option value="extension">Home extension</option><option value="rental">Rental units</option></select></label><label>Location / county<input name="location" required maxLength="100" placeholder="e.g. Kiambu" value={form.location} onChange={change} /></label></div>
        <div className="form-row"><label>Floor area <span className="input-unit">m² per floor</span><input type="number" name="floor_area" min="10" max="20000" step="0.1" required placeholder="e.g. 120" value={form.floor_area} onChange={change} /></label><label>Number of floors<select name="floors" value={form.floors} onChange={change}>{[1, 2, 3, 4, 5].map((floor) => <option key={floor} value={floor}>{floor} floor{floor > 1 ? 's' : ''}</option>)}</select></label></div>
        <fieldset className="finish-options"><legend>Finish level</legend>{[['basic', 'Basic', 'Essential, practical finish'], ['standard', 'Standard', 'A balanced everyday finish'], ['premium', 'Premium', 'Higher-spec finish allowance']].map(([value, title, description]) => <label className="finish-option" key={value}><input type="radio" name="finish_level" value={value} checked={form.finish_level === value} onChange={change} /><span><strong>{title}</strong><small>{description}</small></span></label>)}</fieldset>
        <details className="assumption-details"><summary>Adjust allowances</summary><div className="form-row"><label>Transport allowance <span className="input-unit">%</span><input type="number" name="transport_percent" min="0" max="30" step="0.5" value={form.transport_percent} onChange={change} /></label><label>Contingency <span className="input-unit">%</span><input type="number" name="contingency_percent" min="0" max="30" step="0.5" value={form.contingency_percent} onChange={change} /></label></div></details>
        <button className="button button-large button-full" type="submit" disabled={busy}>{busy ? 'Calculating…' : 'Calculate my estimate'} <span aria-hidden="true">↗</span></button><p className="form-note">Your estimate uses sample rates or your own saved rate overrides.</p>
      </form>
    </div><aside className="estimator-aside"><div className="aside-card aside-dark"><span className="aside-icon">✳</span><h3>A useful starting point, not a final quote.</h3><p>Our calculator uses simplified quantities per square metre. Actual needs vary with design, soil, specifications, site and local practice.</p></div><div className="aside-card"><span className="step-kicker">WHAT’S INCLUDED</span><ul className="included-list"><li>7 core construction materials</li><li>Labour allowance by finish level</li><li>Transport allowance</li><li>Contingency allowance</li><li>Print-friendly itemization</li></ul></div></aside></section>
    {result && <EstimateResults result={result} input={form} user={user} onSave={save} />}
  </>;
}

function EstimateResults({ result, input, user, onSave }) {
  const image = input.project_type === 'rental' ? 'apartment-concept.png' : 'family-home-concept.png';
  return <section className="results-section" id="estimate-results"><div className="results-heading"><div><div className="eyebrow">YOUR PRELIMINARY ESTIMATE</div><h2>{input.project_name || 'Untitled project'}</h2><p>{result.project_type_label} · {input.location} · {Number(input.floor_area).toFixed(1)} m² × {input.floors} floor(s) · {result.finish_label} finish</p></div><button className="button button-outline print-button" type="button" onClick={() => window.print()}>Print / Save PDF</button></div>
    <img className="estimate-house-image" src={`/img/${image}`} alt="Building concept illustration" />
    <div className="results-total"><span>Estimated project total</span><strong>{formatKes(result.total_cost)}</strong><small>Planning estimate only · Sample or personal rates</small></div>
    <EstimateTable result={result} transport={input.transport_percent} contingency={input.contingency_percent} />
    <p className="estimate-disclaimer">Quantities and prices are illustrative. Verify supplier prices and take-offs locally. This is not a professional BOQ or structural design.</p>
    {user ? <button className="button save-estimate-form" type="button" onClick={onSave}>Save this estimate <span aria-hidden="true">↗</span></button> : <p className="save-prompt"><Link to="/register">Create a free account</Link> to save your estimate and your own material rates.</p>}
  </section>;
}

function EstimateTable({ result, transport, contingency }) {
  return <div className="table-wrap"><table className="estimate-table"><thead><tr><th>Material</th><th>Estimated quantity</th><th>Rate</th><th className="amount">Estimated cost</th></tr></thead><tbody>
    {result.items.map((item) => <tr key={item.material_id}><td>{item.name}</td><td>{Number(item.quantity).toFixed(2)} {item.unit}</td><td>{formatKes(item.unit_price)} / {item.unit}</td><td className="amount">{formatKes(item.total_cost)}</td></tr>)}
    <tr className="summary-row"><td colSpan="3">Materials subtotal</td><td className="amount">{formatKes(result.materials_cost)}</td></tr><tr className="summary-row"><td colSpan="3">Labour allowance</td><td className="amount">{formatKes(result.labour_cost)}</td></tr><tr className="summary-row"><td colSpan="3">Transport ({transport}%)</td><td className="amount">{formatKes(result.transport_cost)}</td></tr><tr className="summary-row"><td colSpan="3">Contingency ({contingency}%)</td><td className="amount">{formatKes(result.contingency_cost)}</td></tr>
  </tbody></table></div>;
}

function AuthPage({ mode, onAuth, showFlash }) {
  const navigate = useNavigate();
  const register = mode === 'register';
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [values, setValues] = useState({ name: '', email: '', password: '' });
  const submit = async (event) => {
    event.preventDefault();
    setError('');
    setBusy(true);
    try {
      const response = await api(`/auth/${mode}`, { method: 'POST', body: JSON.stringify(values) });
      updateCsrfToken(response.csrfToken);
      onAuth(response.user);
      showFlash(register ? 'Welcome to Construction Estimator. Your account is ready.' : `Welcome back, ${response.user.name}.`);
      navigate('/dashboard');
    } catch (failure) {
      setError(failure.message);
    } finally {
      setBusy(false);
    }
  };
  return <section className={`auth-layout${register ? '' : ' login-layout'}`}><div className="auth-intro"><div className="eyebrow">{register ? 'GET STARTED FOR FREE' : 'WELCOME BACK'}</div><h1>{register ? <>Your plans.<br /><span>All in one place.</span></> : <>Pick up where<br /><span>you left off.</span></>}</h1><p>{register ? 'Save estimates, revisit project budgets and add the material rates you get from suppliers.' : 'Your saved projects and material rates are ready when you are.'}</p>{register && <ul><li>Save and compare project estimates</li><li>Set your own material price overrides</li><li>Print a clear itemized summary</li></ul>}</div>
    <div className="auth-card"><span className="step-kicker">{register ? 'CREATE AN ACCOUNT' : 'SIGN IN'}</span><h2>{register ? 'Join Construction Estimator' : 'Welcome back'}</h2><p>{register ? 'It only takes a minute.' : 'Enter your account details below.'}</p>{error && <div className="form-error" role="alert">{error}</div>}
      <form className="stack-form" onSubmit={submit}>{register && <label>Your name<input autoComplete="name" minLength="2" maxLength="100" required value={values.name} onChange={(event) => setValues({ ...values, name: event.target.value })} /></label>}<label>Email address<input type="email" autoComplete="email" maxLength="190" required value={values.email} onChange={(event) => setValues({ ...values, email: event.target.value })} /></label><label>Password{register && <span className="optional">At least 10 characters</span>}<input type="password" autoComplete={register ? 'new-password' : 'current-password'} minLength={register ? 10 : undefined} required value={values.password} onChange={(event) => setValues({ ...values, password: event.target.value })} /></label><button className="button button-full" type="submit" disabled={busy}>{busy ? 'Please wait…' : register ? 'Create account' : 'Sign in'} <span>↗</span></button></form>
      <p className="auth-switch">{register ? 'Already have an account? ' : 'New to Construction Estimator? '}<Link to={register ? '/login' : '/register'}>{register ? 'Sign in' : 'Create an account'}</Link></p>
    </div></section>;
}

function Dashboard({ user }) {
  const [estimates, setEstimates] = useState(null);
  const [error, setError] = useState('');
  useEffect(() => { api('/estimates').then((data) => setEstimates(data.estimates)).catch((failure) => setError(failure.message)); }, []);
  const total = estimates?.reduce((sum, item) => sum + Number(item.total_cost), 0) || 0;
  return <>
    <section className="dashboard-hero"><div><div className="eyebrow"><span className="eyebrow-dot" /> YOUR BUILDING PLANS</div><h1>Good to see you, {user.name}.</h1><p>Keep track of your estimates and the budgets you’re exploring.</p></div><Link className="button" to="/estimate">New estimate <span>↗</span></Link></section>
    {error && <div className="form-error">{error}</div>}
    <section className="dashboard-stats"><div className="dashboard-stat"><span>Saved estimates</span><strong>{estimates?.length ?? '…'}</strong></div><div className="dashboard-stat"><span>Combined estimated budgets</span><strong>{formatKes(total)}</strong></div><div className="dashboard-stat"><span>Your rate list</span><Link to="/rates">Manage material rates ↗</Link></div></section>
    <section className="project-list-section"><div className="list-heading"><div><div className="eyebrow">YOUR PROJECTS</div><h2>Saved estimates</h2></div>{Boolean(estimates?.length) && <span className="project-count">{estimates.length} project{estimates.length === 1 ? '' : 's'}</span>}</div>
      {estimates === null ? <p>Loading saved estimates…</p> : estimates.length === 0 ? <div className="empty-state"><h3>Your first project starts here.</h3><p>Run your first estimate to see your budget summary here.</p><Link className="button" to="/estimate">Create an estimate <span>↗</span></Link></div> : <div className="project-table-wrap"><table className="project-table"><thead><tr><th>Project</th><th>Location</th><th>Size</th><th>Saved</th><th className="amount">Estimated budget</th><th /></tr></thead><tbody>{estimates.map((item) => <tr key={item.id}><td><strong>{item.project_name}</strong><small>{capitalize(item.project_type)} · {capitalize(item.finish_level)} finish</small></td><td>{item.location}</td><td>{Number(item.floor_area).toFixed(1)} m² × {item.floors}</td><td>{formatDate(item.created_at)}</td><td className="amount budget-cell">{formatKes(item.total_cost)}</td><td><Link className="project-view-link" to={`/estimates/${item.id}`}>View / print</Link></td></tr>)}</tbody></table></div>}
    </section><div className="dashboard-note"><strong>Remember:</strong> Saved totals are snapshots based on the material rates available when each estimate was created. Confirm prices and quantities before making decisions.</div>
  </>;
}

function Rates({ showFlash }) {
  const [materials, setMaterials] = useState(null);
  const [rates, setRates] = useState({});
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  useEffect(() => { api('/rates').then(({ materials: rows }) => { setMaterials(rows); setRates(Object.fromEntries(rows.map((item) => [item.id, item.override_price ? String(item.price) : '']))); }).catch((failure) => setError(failure.message)); }, []);
  const save = async (event) => {
    event.preventDefault();
    setBusy(true);
    setError('');
    try { await api('/rates', { method: 'PUT', body: JSON.stringify({ rates }) }); showFlash('Your material rates have been updated.'); }
    catch (failure) { setError(failure.message); }
    finally { setBusy(false); }
  };
  return <>
    <PageHero eyebrow="MAKE IT YOURS" title="Set your material rates." className="rates-hero">Replace sample prices with the rates from your suppliers. Leave a field blank to use the sample rate again.</PageHero>
    <section className="rates-content"><div className="rates-callout"><span>i</span><p><strong>Rates are private to your account.</strong> Prices are not verified market averages. Add the source and date to your own project notes outside the app.</p></div>
      <div className="form-card rates-card"><div className="form-card-head"><div><span className="step-kicker">PERSONAL PRICE LIST</span><h2>Materials & unit prices</h2></div><span className="form-number">KSh</span></div>
        {error && <div className="form-error" role="alert">{error}</div>}
        {materials === null ? <p>Loading rates…</p> : <form onSubmit={save}><div className="rates-list">{materials.map((material) => <label className="rate-row" key={material.id}><span><strong>{material.name}</strong><small>Sample: {formatKes(material.default_price)} / {material.unit}{material.override_price ? ` · Your current override: ${formatKes(material.price)}` : ''}</small></span><span className="rate-input"><span>KSh</span><input type="number" min="0.01" max="10000000" step="0.01" placeholder={Number(material.default_price).toFixed(2)} value={rates[material.id] ?? ''} onChange={(event) => setRates({ ...rates, [material.id]: event.target.value })} aria-label={`${material.name} price per ${material.unit}`} /><small>/ {material.unit}</small></span></label>)}</div><div className="rates-actions"><p>Blank fields use sample rates.</p><button className="button" type="submit" disabled={busy}>{busy ? 'Saving…' : 'Save my rates'} <span>↗</span></button></div></form>}
      </div>
    </section>
  </>;
}

function EstimateDetail() {
  const { id } = useParams();
  const [estimate, setEstimate] = useState(null);
  const [error, setError] = useState('');
  useEffect(() => { api(`/estimates/${id}`).then(setEstimate).catch((failure) => setError(failure.message)); }, [id]);
  if (error) return <div className="loading-state" role="alert">{error}</div>;
  if (!estimate) return <div className="loading-state">Loading saved estimate…</div>;
  const image = estimate.project_type === 'rental' ? 'apartment-concept.png' : 'family-home-concept.png';
  return <section className="saved-estimate"><div className="saved-estimate-actions"><Link className="text-link" to="/dashboard">← Back to my projects</Link><button className="button button-outline print-button" type="button" onClick={() => window.print()}>Print / Save PDF</button></div>
    <div className="saved-estimate-heading"><div className="eyebrow">SAVED PRELIMINARY ESTIMATE</div><h1>{estimate.project_name}</h1><p>{capitalize(estimate.project_type)} · {estimate.location} · {Number(estimate.floor_area).toFixed(1)} m² × {estimate.floors} floor(s) · {capitalize(estimate.finish_level)} finish</p><p className="saved-date">Saved {formatDate(estimate.created_at)}</p></div>
    <figure className="saved-estimate-illustration"><img className="estimate-house-image" src={`/img/${image}`} alt="Building concept illustration" /><figcaption>Concept poster · Not a rendering of the actual project</figcaption></figure>
    <div className="results-total"><span>Estimated project total</span><strong>{formatKes(estimate.total_cost)}</strong><small>Planning estimate only · Saved rate snapshot</small></div>
    <EstimateTable result={estimate} transport={estimate.transport_percent} contingency={estimate.contingency_percent} />
    <p className="estimate-disclaimer">This saved estimate reflects rates from the time it was created. Quantities and prices are illustrative; verify supplier prices and take-offs locally. This is not a professional BOQ or structural design.</p>
  </section>;
}

function Pricing() {
  return <>
    <PageHero eyebrow="PRICING TO EXPLORE" title="Start free. Build what people need." className="pricing-hero">Construction Estimator is an early pilot. The ideas below are proposed plans for future development—not active subscriptions or payment offers.</PageHero>
    <section className="pricing-grid"><article className="price-card"><span className="step-kicker">FOR EVERYONE</span><h2>Free</h2><div className="price-amount">KSh 0 <small>/ forever</small></div><p>Get started with early-stage project estimates.</p><ul><li>Basic estimates & printable summaries</li><li>Sample material rates</li><li>Save estimates with a free account</li></ul><Link className="button button-outline button-full" to="/estimate">Start estimating</Link></article>
      <article className="price-card price-featured"><span className="price-tag">FUTURE IDEA</span><span className="step-kicker">FOR INDEPENDENT BUILDERS</span><h2>Contractor</h2><div className="price-amount">KSh 500–1,500 <small>/ month, proposed</small></div><p>Potential tools for contractors who manage more projects.</p><ul><li>Reusable quotations & rate history</li><li>Client sharing</li><li>Branded reports (planned)</li></ul><span className="price-placeholder">Not available yet · No payment collected</span></article>
      <article className="price-card"><span className="step-kicker">FOR SMALL TEAMS</span><h2>Team</h2><div className="price-amount">KSh 2,000–5,000 <small>/ month, proposed</small></div><p>A future option for growing construction teams.</p><ul><li>Shared projects & multiple users</li><li>Company-branded reports</li><li>Team workflows (planned)</li></ul><span className="price-placeholder">Not available yet · No payment collected</span></article>
    </section><section className="pricing-note"><strong>These are validation hypotheses.</strong> Proposed prices are not confirmed market averages. Payments, subscriptions and M-Pesa processing are not implemented.</section>
  </>;
}

function NotFound() {
  return <section className="empty-state"><h1>Page not found</h1><p>The page you requested does not exist.</p><Link className="button" to="/">Return home</Link></section>;
}

function capitalize(value = '') {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default App;
