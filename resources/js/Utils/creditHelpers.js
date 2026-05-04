export function formatCurrency(value) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    maximumFractionDigits: 0,
  }).format(value || 0);
}

export function parseDateString(value) {
  if (!value) {
    return new Date(NaN);
  }

  if (value instanceof Date) {
    return value;
  }

  const stringValue = String(value).trim();
  const isoDate = stringValue.replace(' ', 'T');
  const parsedIso = new Date(isoDate);

  if (!Number.isNaN(parsedIso.getTime())) {
    return parsedIso;
  }

  const parts = stringValue.split(/[-T:\s]/).map(Number).filter(part => !Number.isNaN(part));
  if (parts.length >= 3) {
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }

  return new Date(NaN);
}

export function formatDateToFR(value) {
  if (!value) return '';
  const date = typeof value === 'string' ? parseDateString(value) : value;
  if (date instanceof Date && Number.isNaN(date.getTime())) {
    return '';
  }
  return new Intl.DateTimeFormat('fr-FR').format(date);
}

export function periodDays(periodicite) {
  return periodicite === 'quinzaine' ? 15 : 30;
}

export function calculateRate(taux, tauxManuel) {
  const base = Number(taux) || 0;
  const manual = tauxManuel !== null && tauxManuel !== undefined && tauxManuel !== '' ? Number(tauxManuel) : null;
  return manual > 0 ? manual : base;
}

export function buildScheduleFromForm(form) {
  const montant = Number(form.montant_demande || 0);
  const taux = calculateRate(form.taux, form.taux_manuelle) / 100;
  const nombre = Math.max(1, Number(form.nombre_echeances || 1));
  const mode = form.mode || 'fixe';
  const periodDaysCount = periodDays(form.periodicite || 'mensuelle');
  const start = form.date_debut || new Date().toISOString().slice(0, 10);
  const startDate = parseDateString(start);

  const principalBase = Math.round((montant / nombre) * 100) / 100;
  let remaining = montant;
  const schedule = [];

  for (let i = 1; i <= nombre; i += 1) {
    const interest = mode === 'degressif'
      ? Math.round((remaining * taux) * 100) / 100
      : Math.round((montant * taux) * 100) / 100;

    const principal = i === nombre ? Math.round(remaining * 100) / 100 : principalBase;
    const total = Math.round((principal + interest) * 100) / 100;
    const dueDate = new Date(startDate);
    dueDate.setDate(dueDate.getDate() + (i - 1) * periodDaysCount);

    schedule.push({
      numero: i,
      date: dueDate.toISOString().slice(0, 10),
      principal,
      interest,
      total,
    });

    remaining = Math.round((remaining - principal) * 100) / 100;
  }

  return schedule;
}
