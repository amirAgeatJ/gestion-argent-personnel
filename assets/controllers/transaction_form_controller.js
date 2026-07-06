import { Controller } from '@hotwired/stimulus';

/**
 * Recharge dynamiquement les options du select "catégorie" quand l'utilisateur change le
 * type de transaction (revenu/dépense/virement), en appelant l'endpoint JSON
 * app_transaction_categories. Le filtrage côté serveur (Form Events dans TransactionType)
 * reste la source de vérité en cas de soumission sans JavaScript.
 */
export default class extends Controller {
    static targets = ['type', 'category'];

    connect() {
        this.refresh();
    }

    refresh() {
        const type = this.typeTarget.value;
        const url = `${this.categoryTarget.dataset.url}?type=${encodeURIComponent(type)}`;
        const currentValue = this.categoryTarget.value;

        fetch(url, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((categories) => {
                this.categoryTarget.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '-- Choisir une catégorie --';
                this.categoryTarget.appendChild(placeholder);

                categories.forEach((category) => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    option.selected = category.id === currentValue;
                    this.categoryTarget.appendChild(option);
                });

                this.categoryTarget.disabled = type === 'transfer';
            });
    }
}
