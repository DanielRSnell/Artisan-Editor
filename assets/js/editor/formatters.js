class Pretty {
    constructor() {
        this.defaultConfig = {
            printWidth: 80,
            tabWidth: 2,
            useTabs: false,
            singleQuote: false,
            htmlWhitespaceSensitivity: "css"
        };
    }

    async twig(code) {
        try {
            return prettier.format(code, {
                ...this.defaultConfig,
                parser: "liquid-html",
                plugins: [prettierPluginLiquid],
                singleAttributePerLine: true,
                bracketSameLine: false,
                bracketSpacing: true,
            });
        } catch (error) {
            console.error('Twig formatting error:', error);
            return code;
        }
    }

    async php(code) {
        try {
            return prettier.format(code, {
                ...this.defaultConfig,
                parser: "php",
                plugins: [prettierPlugins.php],
            });
        } catch (error) {
            console.error('PHP formatting error:', error);
            return code;
        }
    }

    async css(code) {
        try {
            return prettier.format(code, {
                ...this.defaultConfig,
                parser: "css",
                plugins: prettierPlugins,
            });
        } catch (error) {
            console.error('CSS formatting error:', error);
            return code;
        }
    }

    async javascript(code) {
        try {
            return prettier.format(code, {
                ...this.defaultConfig,
                parser: "babel",
                plugins: [prettierPlugins.babel],
            });
        } catch (error) {
            console.error('JavaScript formatting error:', error);
            return code;
        }
    }
}

// Initialize plugins
window.prettierPlugins = window.prettierPlugins || {};

// Make class available globally
window.pretty = new Pretty();