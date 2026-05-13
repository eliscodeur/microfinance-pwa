

const db = new Dexie('TontineAppDB');
db.version(72).stores({
    clients: 'id, nom, prenom, telephone',
    carnets: 'id, client_id, numero',
    cycles: '++id, &cycle_uid, carnet_id, statut, synced',
    collectes: '++id, &collecte_uid, cycle_uid, cycle_id, synced'
});
window.db = db; // <--- AJOUTE CETTE LIGNE

async function populateDatabase(data, options = {}) {
    const safeData = data || {};
    try {
        const replaceAll = options.replaceAll === true;

        const normalizedCycles = (safeData.cycles || []).map(c => ({
            ...c,
            id: Number(c.id),
            carnet_id: Number(c.carnet_id),
            cycle_uid: String(c.cycle_uid),
            synced: 1
        }));

        const normalizedCollectes = (safeData.collectes || []).map(coll => ({
            ...coll,
            id: Number(coll.id),
            cycle_id: Number(coll.cycle_id), // On garde l'ID MySQL ici
            cycle_uid: String(coll.cycle_uid), // On utilise l'UID pour le lien Dexie
            collecte_uid: String(coll.collecte_uid),
            montant: Number(coll.montant) || 0,
            synced: 1
        }));

        await db.transaction('rw', [db.clients, db.carnets, db.cycles, db.collectes], async () => {
            if (replaceAll) {
                await Promise.all([db.collectes.clear(), db.cycles.clear(), db.carnets.clear(), db.clients.clear()]);
            }

            if (safeData.clients?.length) await db.clients.bulkPut(safeData.clients);
            if (safeData.carnets?.length) await db.carnets.bulkPut(safeData.carnets);

            if (normalizedCycles.length) {
                const cUids = normalizedCycles.map(c => c.cycle_uid);
                await db.cycles.where('cycle_uid').anyOf(cUids).delete();
                await db.cycles.bulkPut(normalizedCycles);
            }

            if (normalizedCollectes.length) {
                const coUids = normalizedCollectes.map(co => co.collecte_uid);
                await db.collectes.where('collecte_uid').anyOf(coUids).delete();
                await db.collectes.bulkPut(normalizedCollectes);
            }
        });

        return true;
    } catch (error) {
        console.error("ERREUR:", error);
        throw error;
    }
}

const DBManager = {
    async searchCarnets(query) {
        const term = query.toLowerCase();
        const allCarnets = await db.carnets.toArray();
        const allClients = await db.clients.toArray();

        return allCarnets.filter((car) => {
            const client = allClients.find((cli) => cli.id === car.client_id);
            return car.numero.toLowerCase().includes(term)
                || (client && client.nom.toLowerCase().includes(term));
        }).map((car) => {
            const client = allClients.find((cli) => cli.id === car.client_id);
            return {
                ...car,
                client_nom: client ? client.nom : 'Inconnu'
            };
        });
    },

    async getActiveCycle(carnetId) {
        return db.cycles
            .where('carnet_id').equals(carnetId)
            .and((cycle) => cycle.statut === 'en_cours')
            .first();
    },

    async getAgentInfo() {
        return {
            nom: localStorage.getItem('agent_nom'),
            matricule: localStorage.getItem('agent_matricule'),
            photo: localStorage.getItem('agent_photo'),
        };
    }
};

export { db, DBManager, populateDatabase };
