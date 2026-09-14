'use client';

import { createContext, useContext, useMemo } from 'react';

type Dictionary = Record<string, unknown>;

interface TranslationContextValue {
  t: (path: string) => string;
}

const TranslationContext = createContext<TranslationContextValue | null>(null);

function resolve(dictionary: Dictionary, path: string): string {
  const value = path.split('.').reduce<unknown>((current, key) => {
    if (current && typeof current === 'object') {
      return (current as Record<string, unknown>)[key];
    }
    return undefined;
  }, dictionary);

  return typeof value === 'string' ? value : path;
}

export function DictionaryProvider({
  dictionary,
  children,
}: {
  dictionary: Dictionary;
  children: React.ReactNode;
}) {
  const value = useMemo<TranslationContextValue>(
    () => ({ t: (path: string) => resolve(dictionary, path) }),
    [dictionary],
  );

  return <TranslationContext.Provider value={value}>{children}</TranslationContext.Provider>;
}

export function useTranslation(): TranslationContextValue {
  const context = useContext(TranslationContext);
  if (!context) {
    throw new Error('useTranslation must be used within a DictionaryProvider');
  }
  return context;
}
