import React from 'react';
import { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { MaterialSymbol } from 'react-material-symbols';
import { apiFetch, storageUrl } from '../utils/api';
import { useI18n } from '../i18n';

export default function EmbedAnimalList() {
  const [searchParams] = useSearchParams();
  const { t, dir, setLocale } = useI18n();
  const isRtl = dir === 'rtl';
  const [animals, setAnimals] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const lang = searchParams.get('lang');
    if (lang && setLocale) setLocale(lang);
    fetchAnimals();
  }, []);

  const fetchAnimals = async () => {
    try {
      const res = await apiFetch('/api/embed/animals');
      if (res.ok) {
        const data = await res.json();
        setAnimals(data.data || []);
      }
    } catch (e) {
      console.error('Failed to fetch animals:', e);
    } finally {
      setLoading(false);
    }
  };

  const getAge = (dateOfBirth) => {
    if (!dateOfBirth) return null;
    const dob = new Date(dateOfBirth);
    const now = new Date();
    const months = (now.getFullYear() - dob.getFullYear()) * 12 + now.getMonth() - dob.getMonth();
    if (months < 12) return `${months}mo`;
    const yrs = Math.floor(months / 12);
    const rem = months % 12;
    return rem ? `${yrs}y ${rem}mo` : `${yrs}y`;
  };

  const getSpeciesIcon = (species) => {
    const map = { Camel: 'camel', Goat: 'goat', Sheep: 'sheep' };
    return map[species] || 'pets';
  };

  return (
    <div className="min-h-screen bg-[#FAF5F1]">
      <div className="max-w-6xl mx-auto p-4 sm:p-6">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-xl font-bold text-[#002819]">
              <MaterialSymbol icon="pets" size={20} className="inline align-text-bottom mr-1" />
              {t('nav.animals') || 'Animals'}
            </h1>
            <p className="text-xs text-[#717973] mt-0.5">Marketplace</p>
          </div>
          <div className="flex gap-2">
            <button
              onClick={() => fetchAnimals()}
              className="p-2 rounded-lg bg-white border border-[#eeeee9] text-[#717973] hover:text-[#002819] transition"
              title="Refresh"
            >
              <MaterialSymbol icon="refresh" size={18} />
            </button>
          </div>
        </div>

        {loading ? (
          <div className="flex items-center justify-center h-48">
            <div className="animate-spin w-6 h-6 border-3 border-[#002819] border-t-transparent rounded-full" />
          </div>
        ) : animals.length === 0 ? (
          <div className="text-center py-16 bg-white rounded-2xl border border-[#eeeee9]">
            <MaterialSymbol icon="pets" size={48} className="text-[#E3E3DE] mx-auto mb-3" />
            <p className="text-[#717973] font-medium">No animals listed</p>
            <p className="text-xs text-[#717973] mt-1">Check back later for new listings</p>
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            {animals.map(animal => {
              const imageUrl = animal.image ? storageUrl(animal.image) : null;
              return (
                <a
                  key={animal.id}
                  href={`/react.oasis/animals/${animal.id}`}
                  target="_top"
                  className="bg-white rounded-xl border border-[#eeeee9] overflow-hidden hover:shadow-lg hover:border-[#D4AF37]/40 transition-all group block"
                >
                  <div className="aspect-square bg-gradient-to-br from-[#f4f4ef] to-[#e8e8e0] relative overflow-hidden">
                    {imageUrl ? (
                      <img src={imageUrl} alt={animal.animal_id} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <MaterialSymbol icon={getSpeciesIcon(animal.species)} size={48} className="text-[#D4AF37]/30" />
                      </div>
                    )}
                    <div className="absolute top-2 left-2">
                      <span className="px-2 py-0.5 rounded-full text-[9px] font-bold text-white bg-[#002819]/70">
                        {animal.species}
                      </span>
                    </div>
                  </div>
                  <div className="p-3">
                    <h3 className="font-bold text-[#002819] text-sm truncate group-hover:text-[#D4AF37] transition-colors">
                      {animal.name || animal.animal_id}
                    </h3>
                    <p className="text-[11px] text-[#717973] mt-0.5 truncate">
                      {animal.animal_id}{animal.breed ? ` · ${animal.breed}` : ''}
                    </p>
                    <div className="flex items-center justify-between mt-2 pt-2 border-t border-[#f0f0eb]">
                      <div className="flex items-center gap-2 text-[11px] text-[#404943]">
                        {animal.gender && (
                          <MaterialSymbol
                            icon={animal.gender === 'Male' ? 'male' : 'female'}
                            size={14}
                            className={animal.gender === 'Male' ? 'text-blue-500' : 'text-pink-500'}
                          />
                        )}
                        {animal.weight && <span>{animal.weight} kg</span>}
                        {getAge(animal.date_of_birth) && <span>{getAge(animal.date_of_birth)}</span>}
                      </div>
                      {animal.owner && (
                        <p className="text-[10px] text-[#717973] truncate max-w-[70px]">{animal.owner.name}</p>
                      )}
                    </div>
                  </div>
                </a>
              );
            })}
          </div>
        )}

        <div className="text-center mt-6 text-[10px] text-[#717973]">
          {t('embedCodesSection.poweredBy') || 'Powered by'}{' '}
          <a href="/react.oasis/" target="_top" className="text-[#002819] font-semibold hover:underline">
            Oasis Trace
          </a>
        </div>
      </div>
    </div>
  );
}
