import React, { useState, useEffect, useRef } from 'react';
import { FiMapPin, FiSearch, FiX } from 'react-icons/fi';
import { API_BASE_URL } from '../config/api';

interface CitySelectionDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onCitySelect: (city: string) => void;
  currentCity?: string;
}

interface LocationResponse {
  status: string;
  locations: string[];
  total_count: number;
}

const CitySelectionDialog: React.FC<CitySelectionDialogProps> = ({
  isOpen,
  onClose,
  onCitySelect,
  currentCity = ''
}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [suggestions, setSuggestions] = useState<string[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [selectedCity, setSelectedCity] = useState(currentCity);
  const searchInputRef = useRef<HTMLInputElement>(null);
  const suggestionsRef = useRef<HTMLDivElement>(null);

  // Fetch Tamil Nadu locations
  const fetchLocations = async (query: string = '') => {
    try {
      setIsLoading(true);
      const url = query 
        ? `${API_BASE_URL}/tamil_nadu_locations.php?search=${encodeURIComponent(query)}`
        : `${API_BASE_URL}/tamil_nadu_locations.php`;
      
      const response = await fetch(url);
      const data: LocationResponse = await response.json();
      
      if (data.status === 'success') {
        setSuggestions(data.locations);
      } else {
        setSuggestions([]);
      }
    } catch (error) {
      console.error('Error fetching locations:', error);
      setSuggestions([]);
    } finally {
      setIsLoading(false);
    }
  };

  // Handle search input change
  const handleSearchChange = (value: string) => {
    setSearchQuery(value);
    setSelectedCity(value);
    
    if (value.length >= 2) {
      fetchLocations(value);
      setShowSuggestions(true);
    } else if (value.length === 0) {
      fetchLocations();
      setShowSuggestions(true);
    } else {
      setShowSuggestions(false);
    }
  };

  // Handle city selection
  const handleCitySelect = (city: string) => {
    setSelectedCity(city);
    setSearchQuery(city);
    setShowSuggestions(false);
  };

  // Handle save button click
  const handleSave = () => {
    if (selectedCity.trim()) {
      onCitySelect(selectedCity.trim());
      onClose();
    }
  };

  // Handle keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && selectedCity.trim()) {
      handleSave();
    } else if (e.key === 'Escape') {
      onClose();
    }
  };

  // Close suggestions when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        suggestionsRef.current && 
        !suggestionsRef.current.contains(event.target as Node) &&
        searchInputRef.current && 
        !searchInputRef.current.contains(event.target as Node)
      ) {
        setShowSuggestions(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Load initial locations when dialog opens
  useEffect(() => {
    if (isOpen) {
      fetchLocations();
      setShowSuggestions(true);
      // Focus search input after a short delay
      setTimeout(() => {
        searchInputRef.current?.focus();
      }, 100);
    }
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
              <FiMapPin className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <h2 className="text-lg font-semibold text-black">Select Your City</h2>
              <p className="text-sm text-gray-600">Choose your location to see available services</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-black transition-colors"
          >
            <FiX className="w-6 h-6" />
          </button>
        </div>

        {/* Search Input */}
        <div className="p-6">
          <div className="relative">
            <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              ref={searchInputRef}
              type="text"
              placeholder="Search for your city..."
              value={searchQuery}
              onChange={(e) => handleSearchChange(e.target.value)}
              onKeyDown={handleKeyDown}
              className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            />
          </div>

          {/* Suggestions Dropdown */}
          {showSuggestions && (
            <div 
              ref={suggestionsRef}
              className="mt-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg shadow-lg bg-white"
            >
                             {isLoading ? (
                 <div className="p-4 text-center text-gray-500">
                   <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                   <p className="mt-2">Loading cities...</p>
                 </div>
              ) : suggestions.length > 0 ? (
                <div>
                  {suggestions.map((city, index) => (
                    <div
                      key={index}
                      onClick={() => handleCitySelect(city)}
                      className="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
                    >
                                             <div className="flex items-center space-x-3">
                         <FiMapPin className="w-4 h-4 text-blue-500 flex-shrink-0" />
                         <span className="text-black">{city}</span>
                       </div>
                    </div>
                  ))}
                </div>
              ) : searchQuery.length >= 2 ? (
                <div className="p-4 text-center text-gray-500">
                  <FiMapPin className="w-8 h-8 mx-auto mb-2 text-gray-300" />
                  <p>No cities found for "{searchQuery}"</p>
                  <p className="text-sm">Try a different search term</p>
                </div>
              ) : (
                <div className="p-4 text-center text-gray-500">
                  <FiMapPin className="w-8 h-8 mx-auto mb-2 text-gray-300" />
                  <p>Start typing to search cities</p>
                </div>
              )}
            </div>
          )}

          
        </div>

                 {/* Footer */}
         <div className="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
           <button
             onClick={onClose}
             className="px-4 py-2 text-gray-600 hover:text-black transition-colors"
           >
             Cancel
           </button>
           <button
             onClick={handleSave}
             disabled={!selectedCity.trim()}
             className="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
           >
             Save Location
           </button>
         </div>
      </div>
    </div>
  );
};

export default CitySelectionDialog;
