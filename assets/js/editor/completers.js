window.ClientBlocksCompleters = (function() {
  window.debug_completions = true;
  
  const debug = (message, data = null) => {
    if (window.debug_completions) {
      if (data) {
        console.log(`🔍 [Completers] ${message}:`, data);
      } else {
        console.log(`🔍 [Completers] ${message}`);
      }
    }
  };

  debug('Initializing ClientBlocksCompleters');

  window.variables = window.variables || [
    '--bg-base',
    '--bg-surface',
    '--text-1',
    '--text-2',
    '--border-1',
    '--border-2',
    '--accent-1',
    '--accent-2'
  ];

  window.classes = window.classes || [
    'text-center',
    'flex',
    'grid',
    'hidden',
    'block',
    'container',
    'mx-auto',
    'p-4'
  ];

  debug('Initial variables:', window.variables);
  debug('Initial classes:', window.classes);

  const variableManager = {
    add: (...newVars) => {
      debug('Adding variables:', newVars);
      window.variables = [...new Set([...window.variables, ...newVars])];
      debug('Updated variables list:', window.variables);
      return window.variables;
    },
    remove: (...varsToRemove) => {
      debug('Removing variables:', varsToRemove);
      window.variables = window.variables.filter(v => !varsToRemove.includes(v));
      debug('Updated variables list:', window.variables);
      return window.variables;
    },
    get: () => {
      debug('Getting all variables');
      return [...window.variables];
    },
    set: (newVars) => {
      debug('Setting new variables:', newVars);
      if (Array.isArray(newVars)) {
        window.variables = [...newVars];
        debug('Variables list replaced');
      } else {
        debug('WARNING: Attempted to set variables with non-array value');
      }
      return window.variables;
    },
    clear: () => {
      debug('Clearing all variables');
      window.variables = [];
      return window.variables;
    }
  };

  const classManager = {
    add: (...newClasses) => {
      debug('Adding classes:', newClasses);
      window.classes = [...new Set([...window.classes, ...newClasses])];
      debug('Updated classes list:', window.classes);
      return window.classes;
    },
    remove: (...classesToRemove) => {
      debug('Removing classes:', classesToRemove);
      window.classes = window.classes.filter(c => !classesToRemove.includes(c));
      debug('Updated classes list:', window.classes);
      return window.classes;
    },
    get: () => {
      debug('Getting all classes');
      return [...window.classes];
    },
    set: (newClasses) => {
      debug('Setting new classes:', newClasses);
      if (Array.isArray(newClasses)) {
        window.classes = [...newClasses];
        debug('Classes list replaced');
      } else {
        debug('WARNING: Attempted to set classes with non-array value');
      }
      return window.classes;
    },
    clear: () => {
      debug('Clearing all classes');
      window.classes = [];
      return window.classes;
    }
  };

  const configureCssCompletion = (monaco) => {
    debug('Configuring CSS completion provider');
    
    monaco.languages.registerCompletionItemProvider('css', {
      triggerCharacters: ['-'],
      provideCompletionItems: (model, position) => {
        debug('CSS completion triggered', { lineNumber: position.lineNumber, column: position.column });

        const textUntilPosition = model.getValueInRange({
          startLineNumber: 1,
          startColumn: 1,
          endLineNumber: position.lineNumber,
          endColumn: position.column
        });

        const word = model.getWordUntilPosition(position);
        const range = {
          startLineNumber: position.lineNumber,
          endLineNumber: position.lineNumber,
          startColumn: word.startColumn,
          endColumn: word.endColumn
        };

        const varContext = textUntilPosition.match(/var\(\s*$/);
        const hyphenContext = textUntilPosition.endsWith('-');
        const inVariableContext = /--[\w-]*$/.test(textUntilPosition);

        debug('Context detection:', {
          varContext: !!varContext,
          hyphenContext,
          inVariableContext,
          text: textUntilPosition
        });

        if (varContext || hyphenContext || inVariableContext) {
          const suggestions = window.variables.map(variable => {
            let insertText = variable;
            if (hyphenContext && !varContext) {
              insertText = `-${variable.slice(1)}`;
            }

            return {
              label: variable,
              kind: monaco.languages.CompletionItemKind.Variable,
              insertText: insertText,
              range: range,
              documentation: `CSS Variable: ${variable}`,
              detail: 'CSS Custom Property',
              sortText: '0' + variable
            };
          });

          if (hyphenContext) {
            suggestions.unshift({
              label: 'var(--)',
              kind: monaco.languages.CompletionItemKind.Snippet,
              insertText: 'var(--$1)',
              insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
              range: range,
              documentation: 'CSS var() function',
              detail: 'CSS Variable Function',
              sortText: '00var'
            });
          }

          debug('Generated CSS variable suggestions:', suggestions);
          return { suggestions };
        }

        return { suggestions: [] };
      }
    });

    monaco.languages.registerCompletionItemProvider('css', {
      triggerCharacters: ['('],
      provideCompletionItems: (model, position) => {
        const textUntilPosition = model.getValueInRange({
          startLineNumber: 1,
          startColumn: 1,
          endLineNumber: position.lineNumber,
          endColumn: position.column
        });

        if (textUntilPosition.endsWith('var(')) {
          const word = model.getWordUntilPosition(position);
          const range = {
            startLineNumber: position.lineNumber,
            endLineNumber: position.lineNumber,
            startColumn: word.startColumn,
            endColumn: word.endColumn
          };

          const suggestions = window.variables.map(variable => ({
            label: variable,
            kind: monaco.languages.CompletionItemKind.Variable,
            insertText: variable,
            range: range,
            documentation: `CSS Variable: ${variable}`,
            detail: 'CSS Custom Property'
          }));

          debug('Generated var() suggestions:', suggestions);
          return { suggestions };
        }

        return { suggestions: [] };
      }
    });
  };

  const configureHtmlCompletion = (monaco) => {
    debug('Configuring HTML completion provider');
    
    monaco.languages.registerCompletionItemProvider('html', {
      triggerCharacters: ['"', "'", ' ', '-'],
      provideCompletionItems: (model, position) => {
        debug('HTML completion triggered', { lineNumber: position.lineNumber, column: position.column });

        const textUntilPosition = model.getValueInRange({
          startLineNumber: 1,
          startColumn: 1,
          endLineNumber: position.lineNumber,
          endColumn: position.column
        });

        const word = model.getWordUntilPosition(position);
        const range = {
          startLineNumber: position.lineNumber,
          endLineNumber: position.lineNumber,
          startColumn: word.startColumn,
          endColumn: word.endColumn
        };

        const classMatch = textUntilPosition.match(/class\s*=\s*["']([^"']*)$/);
        if (classMatch) {
          debug('Class attribute context detected');
          const currentClasses = classMatch[1].split(' ');
          const lastClass = currentClasses[currentClasses.length - 1];
          
          debug('Class context:', { currentClasses, lastClass });

          const filteredClasses = window.classes.filter(className => {
            const isUsed = currentClasses.slice(0, -1).includes(className);
            const matchesFilter = !lastClass || className.toLowerCase().includes(lastClass.toLowerCase());
            return !isUsed && matchesFilter;
          });

          const suggestions = filteredClasses.map(className => ({
            label: className,
            kind: monaco.languages.CompletionItemKind.Value,
            insertText: className,
            range: range,
            documentation: `Class: ${className}`
          }));

          debug('Generated class suggestions:', suggestions);
          return { suggestions };
        }

        const styleMatch = textUntilPosition.match(/style\s*=\s*["'][^"']*var\(\s*$/);
        if (styleMatch) {
          debug('Style attribute var() context detected');
          const suggestions = window.variables.map(variable => ({
            label: variable,
            kind: monaco.languages.CompletionItemKind.Variable,
            insertText: variable,
            range: range,
            documentation: `CSS Variable: ${variable}`
          }));

          debug('Generated style attribute suggestions:', suggestions);
          return { suggestions };
        }

        return { suggestions: [] };
      }
    });
  };

  debug('ClientBlocksCompleters initialization complete');

  return {
    configureCssCompletion,
    configureHtmlCompletion,
    variables: variableManager,
    classes: classManager
  };
})();
