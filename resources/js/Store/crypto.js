import { defineStore } from 'pinia';
import axios from 'axios';

export const useCryptoStore = defineStore('crypto', {
    state: () => ({
        prices: {},
        marketCaps: {},
        volumes: {},
        icons: {},
        changes: {},
        chartData: {},
        currency: 'USD', // Default currency
    }),

    actions: {
        async fetchTop10CryptoData() {
            const cryptos = [
                { symbol: 'BTC', pair: 'BTC-USD' },
                { symbol: 'ETH', pair: 'ETH-USD' },
                { symbol: 'USDT', pair: 'USDT-USD' },
                { symbol: 'BNB', pair: 'BNB-USD' },
                { symbol: 'SOL', pair: 'SOL-USD' },
                { symbol: 'XRP', pair: 'XRP-USD' },
                { symbol: 'ADA', pair: 'ADA-USD' },
                { symbol: 'DOGE', pair: 'DOGE-USD' },
                { symbol: 'TRX', pair: 'TRX-USD' },
                { symbol: 'AVAX', pair: 'AVAX-USD' },
                { symbol: 'SHIB', pair: 'SHIB-USD' },
                { symbol: 'LINK', pair: 'LINK-USD' },
                { symbol: 'DOT', pair: 'DOT-USD' },
                { symbol: 'BCH', pair: 'BCH-USD' },
                { symbol: 'NEAR', pair: 'NEAR-USD' },
                { symbol: 'LTC', pair: 'LTC-USD' },
                { symbol: 'MATIC', pair: 'MATIC-USD' },
                { symbol: 'UNI', pair: 'UNI-USD' },
                { symbol: 'ICP', pair: 'ICP-USD' },
                { symbol: 'PEPE', pair: 'PEPE-USD' },
            ];

            try {
                const promises = cryptos.map(crypto =>
                    axios.get(`https://api.coinbase.com/v2/prices/${crypto.pair}/spot`)
                );
                const responses = await Promise.all(promises);

                responses.forEach((response, index) => {
                    const crypto = cryptos[index];
                    const symbol = crypto.symbol.toLowerCase();
                    const price = parseFloat(response.data.data.amount).toFixed(2);
                    this.prices[symbol] = price;
                    this.icons[symbol] = `https://assets.coincap.io/assets/icons/${symbol}@2x.png`;
                    // Simulate missing data
                    this.changes[symbol] = (Math.random() * 10 - 5).toFixed(2); // -5% to +5%
                    this.marketCaps[symbol] = (Math.random() * 1000000000).toFixed(0); // Up to 1B
                    this.volumes[symbol] = (Math.random() * 1000000).toFixed(0); // Up to 1M
                });

                console.log('Coinbase data fetched:', this.prices);
            } catch (error) {
                console.error("Error fetching crypto data from Coinbase:", error);
            }
        },

        async fetchChartData(symbol, days = 1) {
            console.warn(`Chart data not available for ${symbol} via Coinbase API`);
            this.chartData[symbol] = null;
        },

        startAutoRefresh() {
            this.fetchTop10CryptoData(); // Initial fetch
            setInterval(() => this.fetchTop10CryptoData(), 60000);
        },

        setCurrency(currency) {
            this.currency = currency;
        },
    },

    getters: {
        getPrice: (state) => (symbol) => state.prices[symbol] || null,
        getMarketCap: (state) => (symbol) => state.marketCaps[symbol] || null,
        getVolume: (state) => (symbol) => state.volumes[symbol] || null,
        getIcon: (state) => (symbol) => state.icons[symbol] || null,
        getChange: (state) => (symbol) => state.changes[symbol] || null,
        getChartData: (state) => (symbol) => state.chartData[symbol] || null,
    },
});