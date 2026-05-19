/**
 * GESTIONNAIRE DE BASE DE DONNÉES (Multi-Agent & Offline-First)
 */

let dbInstance = null;
let currentInstanceMatricule = null;

/**
 * Récupère l'instance Dexie de l'agent actuellement connecté.
 */
export function getAgentDB() {
    const matricule = localStorage.getItem('current_agent_matricule');
    
    if (!matricule) {
        dbInstance = null; // Sécurité : si pas de matricule, on vide l'instance
        return null;
    }

    // 🚩 C'EST ICI QUE ÇA SE JOUE :
    if (dbInstance !== null && currentInstanceMatricule !== matricule) {
        console.log("⚠️ BASCULE DE BASE DETECTÉE !");
        dbInstance.close();      // On ferme la connexion physique à la base A
        dbInstance = null;       // On efface l'objet de la mémoire
        currentInstanceMatricule = null;
    }

    if (dbInstance) return dbInstance;

    // Création de la nouvelle base
    const db = new Dexie(`TontineDB_${matricule}`);
    db.version(3).stores({
        agents: 'id, matricule, synced',
        clients: 'id, nom, prenom, telephone',
        carnets: 'id, client_id, numero',
        cycles: '++id, &cycle_uid, carnet_id, statut, synced',
        collectes: '++id, &collecte_uid, cycle_uid, cycle_id, synced',
        bonus_en_attente: 'id, agent_id, type, statut, date_attribution',
        paiements_valides: 'id, reference, montant_total, type, created_at',
        agent_stats: 'id'
    });
    
    dbInstance = db;
    currentInstanceMatricule = matricule;
    return db;
}

/**
 * OBJET DYNAMIQUE 'db' (Une seule déclaration ici)
 */
export const db = {
    get clients() { return getAgentDB()?.clients; },
    get carnets() { return getAgentDB()?.carnets; },
    get cycles() { return getAgentDB()?.cycles; },
    get collectes() { return getAgentDB()?.collectes; },
    get agent_stats() { return getAgentDB()?.agent_stats; },
    open: () => getAgentDB()?.open(),
    isOpen: () => getAgentDB()?.isOpen() ?? false,
    table: (name) => getAgentDB()?.table(name)
};

/**
 * Remplit la base de données avec les données du serveur
 */
export async function populateDatabase(data, options = {}) {
    const database = getAgentDB();
    if (!database) throw new Error("Base de données non initialisée.");

    if (!data) return false;

    try {
        const replaceAll = options.replaceAll === true;

        await database.transaction('rw', [
            database.clients, 
            database.carnets, 
            database.cycles, 
            database.collectes,
            database.agents,
            database.bonus_en_attente, 
            database.paiements_valides,
            database.agent_stats
        ], async () => {
            
            if (replaceAll) {
                await database.collectes.clear();
                await database.cycles.clear();
                await database.carnets.clear();
                await database.clients.clear();
                await database.bonus_en_attente.clear(); 
                await database.paiements_valides.clear();
                await database.agent_stats.clear();
            }

            // 1. Mise à jour de l'agent (pour le pin_hash notamment)
            if (data.agent) {
                await database.agents.put({
                    ...data.agent,
                    synced: 1,
                    id: Number(data.agent.id)
                });
            }

            // 2. Clients et Carnets
            if (data.clients?.length) await database.clients.bulkPut(data.clients);
            if (data.carnets?.length) await database.carnets.bulkPut(data.carnets);

            // 3. Fusion des retraits dans les cycles
            if (data.cycles?.length) {
                const normalizedCycles = data.cycles.map(cycle => {
                    // On filtre les retraits envoyés par l'API pour ce cycle spécifique
                    const retraitsAssocies = (data.retraits || []).filter(r => 
                        r.cycle_uid === cycle.cycle_uid || Number(r.cycle_id) === Number(cycle.id)
                    );

                    // Calcul de la somme des retraits
                    const sommeRetraits = retraitsAssocies.reduce((total, r) => total + Number(r.montant), 0);

                    return { 
                        ...cycle, 
                        id: isNaN(cycle.id) ? cycle.id : Number(cycle.id), 
                        synced: 1,
                        // ON INJECTE LES DONNÉES ICI :
                        liste_retraits: retraitsAssocies, 
                        total_retraits_cumule: sommeRetraits,
                        // Le solde net est déjà fourni par ton objet (6006 dans ton exemple)
                        solde_net_final: Number(cycle.solde_restant_net || 0)
                    };
                });

                await database.cycles.bulkPut(normalizedCycles);
            }

            // 4. Collectes
            if (data.collectes?.length) {
                const normalizedCollectes = data.collectes.map(coll => ({
                    ...coll,
                    id: isNaN(coll.id) ? coll.id : Number(coll.id),
                    synced: 1
                }));
                await database.collectes.bulkPut(normalizedCollectes);
            }

            if (data.bonus_en_attente) {
                try {
                    await database.bonus_en_attente.clear();
                    if (data.bonus_en_attente.length > 0) {
                        await database.bonus_en_attente.bulkPut(data.bonus_en_attente);
                        console.log("✅ Bonus enregistrés avec succès :", data.bonus_en_attente.length);
                    }
                } catch (error) {
                    console.error("❌ Erreur Dexie sur bonus_en_attente :", error);
                }
            }

            if (data.historique_paiements) {
                try {
                    await database.paiements_valides.clear();
                    if (data.historique_paiements.length > 0) {
                        await database.paiements_valides.bulkPut(data.historique_paiements);
                        console.log("✅ Historique paiements enregistré avec succès :", data.historique_paiements.length);
                    }
                } catch (error) {
                    console.error("❌ Erreur Dexie sur historique_paiements :", error);
                }
            }

            if (data.stats_performance && data.agent) {
                try {
                    await database.agent_stats.put({
                        id: Number(data.agent.id), // Utilise l'ID de l'agent comme clé unique
                        volume_historique_global: Number(data.stats_performance.volume_historique_global || 0),
                        total_historique_cycles_termines: Number(data.stats_performance.total_historique_cycles_termines || 0),
                        historique_courbe: data.stats_performance.historique_courbe || [] // Le tableau complet pour le graphique
                    });
                    console.log("✅ Statistiques de performance de l'agent mises à jour.");
                } catch (error) {
                    console.error("❌ Erreur Dexie sur agent_stats :", error);
                }
            }
        });
        return true;
    } catch (error) {
        console.error(`❌ Erreur lors du remplissage :`, error);
        throw error;
    }
}
/**
 * Gestionnaire métier pour les recherches et cycles
 */
export const DBManager = {
    async searchCarnets(query) {
        const database = getAgentDB();
        if (!database) return [];
        const term = query.toLowerCase();
        
        const allClients = await database.clients.toArray();
        const filteredClientIds = allClients
            .filter(c => (c.nom + " " + c.prenom).toLowerCase().includes(term))
            .map(c => c.id);

        const carnets = await database.carnets
            .filter(car => car.numero.toLowerCase().includes(term) || filteredClientIds.includes(car.client_id))
            .toArray();

        return carnets.map(car => {
            const client = allClients.find(c => c.id === car.client_id);
            return {
                ...car,
                client_nom: client ? `${client.nom} ${client.prenom}` : 'Inconnu'
            };
        });
    },

    async getActiveCycle(carnetId) {
        const database = getAgentDB();
        if(!database) return null;
        return database.cycles
            .where('carnet_id').equals(isNaN(carnetId) ? carnetId : Number(carnetId))
            .filter(c => c.statut === 'en_cours')
            .first();
    }
};