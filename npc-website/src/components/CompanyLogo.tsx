import React from 'react';
import { FiShield } from 'react-icons/fi';

interface CompanyLogoProps {
  size?: 'sm' | 'md' | 'lg' | 'xl';
  className?: string;
  showText?: boolean;
}

const CompanyLogo: React.FC<CompanyLogoProps> = ({ 
  size = 'md', 
  className = '', 
  showText = false 
}) => {
  const containerClasses = {
    sm: 'w-6 h-6',
    md: 'w-8 h-8',
    lg: 'w-12 h-12',
    xl: 'w-16 h-16'
  };

  const textClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl'
  };

  return (
    <div className="flex items-center space-x-2">
      <div className={`bg-black rounded-lg flex items-center justify-center overflow-hidden ${containerClasses[size]} ${className}`}>
        <img 
          src="/images/logo-npc.png" 
          alt="NPC Pest Control Logo"
          className={`${containerClasses[size]} object-contain`}
          onError={(e) => {
            // Fallback to shield icon if logo fails to load
            e.currentTarget.style.display = 'none';
            e.currentTarget.nextElementSibling?.classList.remove('hidden');
          }}
        />
        <FiShield className={`text-white hidden ${containerClasses[size]}`} />
      </div>
      {showText && (
        <span className={`font-bold text-gray-900 ${textClasses[size]}`}>
          npc
        </span>
      )}
    </div>
  );
};

export default CompanyLogo; 