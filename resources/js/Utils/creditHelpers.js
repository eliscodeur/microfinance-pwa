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
  
  // 1. On prend le taux directement (pas de division par 12 ou 24)
  const tauxAnnuel = calculateRate(form.taux, form.taux_manuel);
  const tauxFixeGlobal = tauxAnnuel / 100;

  // 2. Calcul des intérêts totaux dès le départ
  const interetTotal = Math.round(montant * tauxFixeGlobal * 100) / 100;
  
  const nombre = Math.max(1, Number(form.nombre_echeances || 1));
  const differe = Math.max(0, Number(form.differe || 0));
  const periodicite = form.periodicite || 'mensuelle';
  const start = form.date_debut || new Date().toISOString().slice(0, 10);
  
  const startDate = typeof parseDateString === 'function' 
    ? parseDateString(start) 
    : new Date(start + 'T00:00:00');

  // 3. Répartition du principal et des intérêts
  const echeancesAmortissables = Math.max(1, nombre - differe);
  const principalParEcheance = Math.round((montant / echeancesAmortissables) * 100) / 100;
  const interetParEcheance = Math.round((interetTotal / nombre) * 100) / 100;
  
  const schedule = [];

  for (let i = 1; i <= nombre; i += 1) {
    // Le principal est 0 pendant le différé
    let principal = (i <= differe) ? 0 : principalParEcheance;
    
    // Si c'est la dernière échéance, on ajuste le principal pour retomber juste sur le montant
    if (i === nombre) {
       const totalDejaAmorti = principalParEcheance * (echeancesAmortissables - 1);
       principal = Math.round((montant - totalDejaAmorti) * 100) / 100;
    }

    const total = Math.round((principal + interetParEcheance) * 100) / 100;

    // Gestion des dates
    const dueDate = new Date(startDate);
    if (periodicite === 'mensuelle') dueDate.setMonth(dueDate.getMonth() + (i - 1));
    else if (periodicite === 'quinzaine') dueDate.setDate(dueDate.getDate() + (i - 1) * 14);
    else {
      const periodDaysCount = typeof periodDays === 'function' ? periodDays(periodicite) : 30;
      dueDate.setDate(dueDate.getDate() + (i - 1) * periodDaysCount);
    }

    schedule.push({
      numero: i,
      date: dueDate.toISOString().slice(0, 10),
      principal,
      interest: interetParEcheance,
      total,
      is_differe: i <= differe
    });
  }

  return schedule;
}