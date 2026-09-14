'use client';

import { useState } from 'react';
import { TabsSelector } from '@/components/common/atoms/TabsSelector';
import { useTranslation } from '@/i18n/DictionaryProvider';
import { SignInForm } from './SignInForm';
import { SignUpForm } from './SignUpForm';

export function AuthPanel() {
  const { t } = useTranslation();
  const [activeTab, setActiveTab] = useState('signin');

  const options = [
    { label: t('auth.tabSignIn'), value: 'signin' },
    { label: t('auth.tabSignUp'), value: 'signup' },
  ];

  return (
    <section className="flex w-[30vw] min-h-[90vh] flex-col justify-center gap-6 bg-neutral p-6 text-tertiary">
      <div className="flex justify-center">
        <TabsSelector
          options={options}
          currentValue={activeTab}
          onChange={setActiveTab}
          activeBgColor="primary"
          activeTextColor="neutral"
          inactiveBgColor="neutral"
          inactiveTextColor="tertiary"
          activeClassName="border-0 font-semibold hover:bg-primary/90"
          inactiveClassName="border-0"
        />
      </div>
      {activeTab === 'signin' ? <SignInForm /> : <SignUpForm />}
    </section>
  );
}
