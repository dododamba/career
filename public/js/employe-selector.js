// public/js/employe-selector.js
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser Select2 avec recherche AJAX
    $('.select2-employe').select2({
        theme: 'classic',
        allowClear: true,
        placeholder: 'Rechercher un employé...',
        minimumInputLength: 2,
        ajax: {
            url: '/employe/search',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    term: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                
                return {
                    results: data.results,
                    pagination: {
                        more: false // Pas de pagination pour simplifier
                    }
                };
            },
            cache: true
        },
        language: {
            errorLoading: function() {
                return "Impossible de charger les résultats";
            },
            inputTooShort: function(args) {
                const remainingChars = args.minimum - args.input.length;
                return `Veuillez saisir ${remainingChars} caractère(s) de plus`;
            },
            noResults: function() {
                return "Aucun employé trouvé";
            },
            searching: function() {
                return "Recherche en cours...";
            }
        }
    });
    
    // Charger les informations de l'employé lorsqu'on change la sélection
    $('.select2-employe').on('change', function() {
        const employeId = $(this).val();
        if (!employeId) {
            $('#employee-info').hide();
            document.getElementById('solde-initial').textContent = "0";
            document.getElementById('solde-restant').textContent = "0";
            document.getElementById('solde-restant').setAttribute('data-solde', "0");
            updateSoldeRestant();
            return;
        }
        
        // Afficher un indicateur de chargement
        $('#employee-details').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>');
        $('#employee-info').show();
        
        // Charger les informations de l'employé via AJAX
        fetch(`/employe/${employeId}/solde`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                // Mettre à jour les informations de solde
                document.getElementById('solde-initial').textContent = data.soldeConges;
                document.getElementById('solde-restant').setAttribute('data-solde', data.soldeConges);
                
                // Construire le HTML des détails de l'employé
                let detailsHtml = `
                    <div class="employee-card">
                        <p><strong>Nom complet:</strong> ${data.prenom} ${data.nom}</p>
                `;
                
                // Ajouter des informations conditionnelles
                if (data.service) {
                    detailsHtml += `<p><strong>Service:</strong> ${data.service}</p>`;
                }
                
                if (data.poste) {
                    detailsHtml += `<p><strong>Poste:</strong> ${data.poste}</p>`;
                }
                
                detailsHtml += `
                        <p><strong>Solde de congés:</strong> ${data.soldeConges} jour(s)</p>
                    </div>
                `;
                
                // Afficher les détails de l'employé
                $('#employee-details').html(detailsHtml);
                $('#employee-info').show();
                
                // Mettre à jour les calculs
                updateSoldeRestant();
            })
            .catch(error => {
                console.error('Erreur lors du chargement des informations:', error);
                $('#employee-details').html(`
                    <div class="alert alert-danger">
                        Impossible de charger les informations de l'employé.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            });
    });
});