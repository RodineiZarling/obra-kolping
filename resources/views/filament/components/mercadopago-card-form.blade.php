<div
    x-data="mercadoPagoCardForm(@json($publicKey), @json($amount), @json($email ?? ''))"
    class="space-y-4"
>
    <form id="form-checkout" class="flex flex-col gap-4">
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Número do Cartão</label>
            <div id="form-checkout__cardNumber" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 mt-1"></div>
        </div>

        <div class="flex gap-4">
            <div class="flex-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Vencimento (MM/AA)</label>
                <div id="form-checkout__expirationDate" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 mt-1"></div>
            </div>
            <div class="flex-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">CVC</label>
                <div id="form-checkout__securityCode" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 mt-1"></div>
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Titular do Cartão</label>
            <input type="text" id="form-checkout__cardholderName" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 w-full text-gray-900 dark:text-white mt-1" />
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Banco Emissor</label>
            <select id="form-checkout__issuer" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 w-full text-gray-900 dark:text-white mt-1"></select>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Parcelas</label>
            <select id="form-checkout__installments" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 w-full text-gray-900 dark:text-white mt-1"></select>
        </div>

        <div class="flex gap-4">
            <div class="w-1/3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo Doc.</label>
                <select id="form-checkout__identificationType" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 w-full text-gray-900 dark:text-white mt-1"></select>
            </div>
            <div class="flex-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Número do Documento</label>
                <input type="text" id="form-checkout__identificationNumber" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 w-full text-gray-900 dark:text-white mt-1" />
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
            <input type="email" id="form-checkout__cardholderEmail" class="h-10 px-3 py-2 border rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 w-full text-gray-900 dark:text-white mt-1" />
        </div>

        <template x-if="error">
            <div class="p-3 text-sm text-red-600 bg-red-100 rounded-lg" x-text="error"></div>
        </template>

        <button
            type="submit"
            id="form-checkout__submit"
            class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-primary fi-btn-color-primary fi-size-md fi-btn-has-label bg-primary-600 text-white hover:bg-primary-500 py-2"
            :disabled="loading"
        >
            <span x-show="!loading">Pagar R$ {{ number_format($amount, 2, ',', '.') }}</span>
            <span x-show="loading">Processando...</span>
        </button>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    if (Alpine.components && Alpine.components.mercadoPagoCardForm) return;

    Alpine.data('mercadoPagoCardForm', (publicKey, amount, email) => ({
        mp: null,
        cardForm: null,
        loading: false,
        error: null,
        publicKey: publicKey,
        amount: amount,
        email: email,

        async init() {
            console.log('Iniciando componente de cartão Mercado Pago');
            console.log('Amount:', this.amount);

            const existingScript = document.getElementById('mp-sdk-script');

            if (typeof MercadoPago === 'undefined' && !existingScript) {
                console.log('Carregando SDK Mercado Pago pela primeira vez...');
                const script = document.createElement('script');
                script.id = 'mp-sdk-script';
                script.src = 'https://sdk.mercadopago.com/js/v2';
                script.async = true;
                script.onload = () => {
                    console.log('SDK Mercado Pago carregada com sucesso via onload');
                    this.initMP();
                };
                script.onerror = () => {
                    console.error('Erro ao carregar SDK Mercado Pago');
                    this.error = 'Não foi possível carregar o processador de pagamentos.';
                };
                document.head.appendChild(script);
            } else if (typeof MercadoPago !== 'undefined') {
                console.log('SDK Mercado Pago já presente globalmente');
                setTimeout(() => this.initMP(), 300);
            } else if (existingScript) {
                console.log('SDK Mercado Pago já em carregamento, aguardando...');
                let checkCount = 0;
                const interval = setInterval(() => {
                    checkCount++;
                    if (typeof MercadoPago !== 'undefined') {
                        clearInterval(interval);
                        console.log('SDK Mercado Pago detectada após espera');
                        this.initMP();
                    } else if (checkCount > 50) {
                        clearInterval(interval);
                        this.error = 'Tempo limite excedido ao carregar a SDK do Mercado Pago.';
                    }
                }, 100);
            }
        },

        initMP() {
            try {
                console.log('--- Executando initMP ---');

                if (this.cardForm) {
                    console.log('Limpando cardForm anterior...');
                    try {
                        this.cardForm.unmount();
                    } catch (e) {
                        console.warn('Erro ao desmontar cardForm:', e);
                    }
                    this.cardForm = null;
                }

                if (!document.getElementById('form-checkout__cardNumber')) {
                    console.warn('Elementos do formulário não encontrados no DOM. Tentando novamente em 500ms...');
                    setTimeout(() => this.initMP(), 500);
                    return;
                }

                console.log('Chamando initMP com publicKey:', this.publicKey);
                if (!this.publicKey) {
                    throw new Error('Public Key do Mercado Pago não configurada.');
                }

                const normalizedAmount = typeof this.amount === 'string'
                    ? this.amount.replace(',', '.')
                    : this.amount;
                const parsedAmount = Number(normalizedAmount);
                if (!parsedAmount || Number.isNaN(parsedAmount)) {
                    throw new Error('Valor da parcela inválido para pagamento.');
                }
                this.amount = parsedAmount;

                // Pre-fill email field if available
                if (this.email) {
                    const emailField = document.getElementById('form-checkout__cardholderEmail');
                    if (emailField && !emailField.value) {
                        emailField.value = this.email;
                    }
                }

                this.mp = new MercadoPago(this.publicKey, {
                    locale: 'pt-BR'
                });

                console.log('Configurando cardForm com amount:', this.amount);
                this.cardForm = this.mp.cardForm({
                    amount: this.amount.toString(),
                    iframe: true,
                    form: {
                        id: 'form-checkout',
                        cardNumber: {
                            id: 'form-checkout__cardNumber',
                            placeholder: 'Número do cartão',
                        },
                        expirationDate: {
                            id: 'form-checkout__expirationDate',
                            placeholder: 'MM/YY',
                        },
                        securityCode: {
                            id: 'form-checkout__securityCode',
                            placeholder: 'CVC',
                        },
                        cardholderName: {
                            id: 'form-checkout__cardholderName',
                            placeholder: 'Titular do cartão',
                        },
                        issuer: {
                            id: 'form-checkout__issuer',
                            placeholder: 'Banco emissor',
                        },
                        installments: {
                            id: 'form-checkout__installments',
                            placeholder: 'Parcelas',
                        },
                        identificationType: {
                            id: 'form-checkout__identificationType',
                            placeholder: 'Tipo de documento',
                        },
                        identificationNumber: {
                            id: 'form-checkout__identificationNumber',
                            placeholder: 'Número do documento',
                        },
                        cardholderEmail: {
                            id: 'form-checkout__cardholderEmail',
                            placeholder: 'E-mail',
                        },
                    },
                    style: {
                        fontSize: '16px',
                        color: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? '#ffffff' : '#000000',
                        placeholderColor: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? '#9ca3af' : '#6b7280',
                    },
                    callbacks: {
                        onFormMounted: error => {
                            if (error) {
                                console.warn('Form Mounted error: ', error);
                                let mountError = 'Erro ao montar o formulário de cartão.';
                                if (Array.isArray(error)) {
                                    mountError += ' ' + error.map(e => e.message || e.description).join(', ');
                                }
                                this.error = mountError;
                                return;
                            }
                            console.log('Formulário montado com sucesso');
                        },
                        onSubmit: async event => {
                            event.preventDefault();
                            this.loading = true;
                            this.error = null;

                            const formData = this.cardForm.getCardFormData();
                            console.log('Enviando pagamento...', formData);

                            const {
                                token,
                                issuerId,
                                paymentMethodId,
                                installments,
                            } = formData;

                            try {
                                const result = await this.$wire.processCardPayment({
                                    token,
                                    issuer_id: issuerId,
                                    payment_method_id: paymentMethodId,
                                    installments,
                                });

                                if (result && result.error) {
                                    this.error = result.error;
                                }
                            } catch (e) {
                                console.error('Erro no processamento:', e);
                                this.error = 'Ocorreu um erro ao processar o pagamento.';
                            } finally {
                                this.loading = false;
                            }
                        },
                        onFetching: (resource) => {
                            console.log('Buscando recurso MP:', resource);
                        },
                        onValidityChange: (error, field) => {
                            if (error) {
                                console.log('Validação do campo:', field, error);
                            }
                        }
                    },
                });
            } catch (err) {
                console.error('Erro detalhado ao inicializar MP:', err);
                let msg = err.message || 'Erro interno ao inicializar o pagamento.';
                if (err.cause && Array.isArray(err.cause)) {
                    msg += ' Detalhes: ' + err.cause.map(c => c.description).join(', ');
                }
                this.error = msg;
            }
        }
    }));
});
</script>
