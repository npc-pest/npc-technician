import React, { useState, useEffect, useRef, useMemo } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { FiShield, FiClock, FiStar, FiUsers, FiCheckCircle, FiArrowRight, FiFacebook, FiInstagram, FiLinkedin, FiTag, FiCopy, FiCheck, FiChevronRight, FiUser } from 'react-icons/fi';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../services/api';
import { API_BASE_URL } from '../config/api';

const LandingPage: React.FC = () => {
  const { isAuthenticated, user } = useAuth();
  const navigate = useNavigate();
  const [isScrolled, setIsScrolled] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 50);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const handleGetStarted = () => {
    if (isAuthenticated) {
      const userRole = localStorage.getItem('userRole');
      if (userRole === 'Admin') {
        navigate('/admin-dashboard');
      } else if (userRole === 'Technician') {
        navigate('/technician-dashboard');
      } else {
        navigate('/dashboard'); // User dashboard
      }
    } else {
      navigate('/otp-login');
    }
  };

  const features = [
    {
      icon: <FiShield className="w-8 h-8 text-blue-600" />,
      title: 'Professional Service',
      description: 'Trained and certified technicians for reliable pest control solutions'
    },
    {
      icon: <FiClock className="w-8 h-8 text-blue-600" />,
      title: 'Same Day Service',
      description: 'Get a pest professional out fast with our same-day appointments'
    },
    {
      icon: <FiStar className="w-8 h-8 text-blue-600" />,
      title: 'Quality Guaranteed',
      description: 'We stand behind our work with satisfaction guarantees'
    },
    {
      icon: <FiUsers className="w-8 h-8 text-blue-600" />,
      title: 'Expert Team',
      description: 'Experienced professionals dedicated to solving your pest problems'
    }
  ];

  const [services, setServices] = useState<any[]>([]);

  // --- Offers (Smart Savings) ---
  type Offer = {
    id: string;
    title: string;
    description: string;
    code: string;
    type: 'percent' | 'flat';
    value: number; // percent or flat value
    minOrder?: number;
    expiresAt: string; // ISO
    serviceName: string;
    badge?: 'New Users' | 'Limited Time' | 'Popular';
  };

  const [nowTs, setNowTs] = useState<number>(Date.now());
  useEffect(() => {
    const id = setInterval(() => setNowTs(Date.now()), 1000);
    return () => clearInterval(id);
  }, []);

  const [copiedCode, setCopiedCode] = useState<string>('');
  const [offers, setOffers] = useState<Offer[]>([]);
  useEffect(() => {
    (async () => {
      try {
        const res: any = await apiService.getOffers();
        const dbOffers = (res?.offers || []).map((o: any): Offer => ({
          id: String(o.offer_id ?? o.id ?? Math.random()),
          title: o.offer_name || o.title || 'Offer',
          description: o.offer_description || o.description || '',
          code: o.offer_code || o.code || '',
          type: (String(o.offer_type || o.type || 'percent').toLowerCase() === 'flat') ? 'flat' : 'percent',
          value: Number(o.offer_percentage ?? o.value ?? 0),
          minOrder: o.min_order_amount ? Number(o.min_order_amount) : undefined,
          expiresAt: o.offer_end_date ? new Date(o.offer_end_date).toISOString() : new Date(Date.now() + 1000*60*60*6).toISOString(),
          serviceName: o.service_name || o.service || 'General Pest Control',
          badge: o.badge || (o.is_new_user ? 'New Users' : (o.is_limited ? 'Limited Time' : undefined)),
        }));
        setOffers(dbOffers);
      } catch (e) {
        console.error('Failed to fetch offers for landing page', e);
        setOffers([]);
      }
    })();
  }, []);

  // Active (non-expired) offers only
  const activeOffers = useMemo(
    () => offers.filter((o) => new Date(o.expiresAt).getTime() > nowTs),
    [offers, nowTs]
  );

  const formatRemaining = (iso: string) => {
    const diff = new Date(iso).getTime() - nowTs;
    if (diff <= 0) return 'Expired';
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    const pad = (n: number) => n.toString().padStart(2, '0');
    return `${pad(h)}:${pad(m)}:${pad(s)}`;
  };

  const copyCode = async (code: string) => {
    try {
      await navigator.clipboard.writeText(code);
      setCopiedCode(code);
      setTimeout(() => setCopiedCode(''), 1500);
    } catch {}
  };

  // General, unique copy for offers (hero + up to 3 cards)
  const offerCopyPool = [
    'Professional-grade treatment with transparent pricing and trusted technicians.',
    'Limited-time value on our most booked services, backed by warranty and care.',
    'Book today and keep your space pest‑free with safe, effective solutions.',
    'Smart savings designed for homes and businesses that expect better.'
  ];
  const getOfferCopy = (_offer: Offer, idx: number) => offerCopyPool[idx % offerCopyPool.length];

  const getImageUrl = (imagePath?: string): string | null => {
    if (!imagePath) return null;
    if (
      imagePath.startsWith('http://') ||
      imagePath.startsWith('https://') ||
      imagePath.startsWith('data:')
    ) {
      return imagePath;
    }
    return `${API_BASE_URL}${imagePath.replace(/^\/+/,'')}`;
  };

  useEffect(() => {
    // Fetch all services from backend for landing display
    (async () => {
      try {
        const res: any = await apiService.getServices();
        // Expecting res.services or res.data
        const list = res?.services || res?.data || [];
        setServices(list);
      } catch (e) {
        console.error('Failed to fetch services for landing page:', e);
      }
    })();
  }, []);

  // --- Reviews Carousel ---
  const reviews = [
    {
      name: 'Aarav Gupta',
      role: 'Home Owner',
      text:
        'The team was professional and on time. No more cockroaches after a single visit. Highly recommend!',
      rating: 5,
      avatar: '/images/user/1.webp',
    },
    {
      name: 'Priya Sharma',
      role: 'Apartment Resident',
      text:
        'Smooth booking experience and excellent service quality. The technician explained everything clearly.',
      rating: 4,
      avatar: '/images/user/2.webp',
    },
    {
      name: 'Rahul Verma',
      role: 'Cafe Owner',
      text:
        'Great result with the rodent control service. Our kitchen is finally pest‑free and safe.',
      rating: 3,
      avatar: '/images/user/3.webp',
    },
    {
      name: 'Sneha Nair',
      role: 'Working Professional',
      text:
        'Booked same‑day service and they delivered! Courteous staff and affordable pricing.',
      rating: 5,
      avatar: '/images/user/4.webp',
    },
  ];

  const carouselRef = useRef<HTMLDivElement | null>(null);
  const [activeIndex, setActiveIndex] = useState(0);
  const activePhysicalIndexRef = useRef(0);
  const VISIBLE_COUNT = 3;
  const loopCopies = 3; // middle copy allows seamless infinite looping
  const loopedReviews = Array(loopCopies).fill(null).flatMap(() => reviews);

  // Auto-advance one card at a time, pause briefly, and loop
  useEffect(() => {
    const container = carouselRef.current;
    if (!container) return;
    const SCROLL_DURATION_MS = 800;
    const PAUSE_BETWEEN_CARDS_MS = 4000;

    const getCards = () =>
      Array.from(
        container.querySelectorAll('[data-review-card="true"]')
      ) as HTMLElement[];

    // Helper: center a card in the viewport
    const centerCard = (el: HTMLElement) => {
      const left = el.offsetLeft - (container.clientWidth - el.clientWidth) / 2;
      container.scrollTo({ left, behavior: 'smooth' });
    };

    // On scroll, find the card closest to center and highlight
    const handleScroll = () => {
      const cards = getCards();
      if (cards.length === 0) return;
      const center = container.scrollLeft + container.clientWidth / 2;
      let nearestIdx = 0;
      let nearestDist = Number.POSITIVE_INFINITY;
      cards.forEach((el, i) => {
        const cardCenter = el.offsetLeft + el.clientWidth / 2;
        const dist = Math.abs(cardCenter - center);
        if (dist < nearestDist) {
          nearestDist = dist;
          nearestIdx = i;
        }
      });
      activePhysicalIndexRef.current = nearestIdx;
      setActiveIndex(nearestIdx % reviews.length);

      // If we drifted into the duplicated half, snap back seamlessly
      if (nearestIdx >= reviews.length + 1) {
        setTimeout(() => {
          const baseIdx = nearestIdx % reviews.length;
          const baseEl = cards[baseIdx];
          if (baseEl) {
            container.scrollLeft = baseEl.offsetLeft - (container.clientWidth - baseEl.clientWidth) / 2;
            activePhysicalIndexRef.current = baseIdx;
            setActiveIndex(baseIdx % reviews.length);
          }
        }, SCROLL_DURATION_MS + 50);
      }
    };

    container.addEventListener('scroll', handleScroll, { passive: true });

    // Initial center on the first card of the middle copy
    requestAnimationFrame(() => {
      const cards = getCards();
      const startIdx = reviews.length; // middle copy start
      if (cards[startIdx]) centerCard(cards[startIdx]);
      activePhysicalIndexRef.current = startIdx;
      setActiveIndex(0);
    });

    const intervalId = setInterval(() => {
      const cards = getCards();
      if (cards.length === 0) return;
      let nextIdx = activePhysicalIndexRef.current + 1;
      if (nextIdx >= cards.length) nextIdx = 0;
      const target = cards[nextIdx];
      centerCard(target);
      activePhysicalIndexRef.current = nextIdx;
      setActiveIndex(nextIdx % reviews.length);

      // If we move beyond the middle copy into the third copy, snap back by one copy without visual jump
      if (nextIdx >= 2 * reviews.length) {
        setTimeout(() => {
          const baseIdx = nextIdx - reviews.length; // same card in middle copy
          const baseEl = cards[baseIdx];
          if (baseEl) {
            container.scrollLeft = baseEl.offsetLeft - (container.clientWidth - baseEl.clientWidth) / 2;
            activePhysicalIndexRef.current = baseIdx;
            setActiveIndex(baseIdx % reviews.length);
          }
        }, SCROLL_DURATION_MS + 30);
      }
    }, PAUSE_BETWEEN_CARDS_MS);

    return () => {
      container.removeEventListener('scroll', handleScroll as any);
      clearInterval(intervalId);
    };
  }, [reviews.length]);

  return (
    <div className="min-h-screen bg-white">
      {/* Navigation */}
      <nav className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        isScrolled ? 'bg-white shadow-lg' : 'bg-transparent'
      }`}>
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center space-x-2">
              <div className="w-8 h-8 bg-black rounded-lg flex items-center justify-center overflow-hidden">
                <img 
                  src="/images/logo-npc.png" 
                  alt="NPC Pest Control Logo"
                  className="w-6 h-6 object-contain"
                  onError={(e) => {
                    // Fallback to shield icon if logo fails to load
                    e.currentTarget.style.display = 'none';
                    e.currentTarget.nextElementSibling?.classList.remove('hidden');
                  }}
                />
                <FiShield className="w-5 h-5 text-white hidden" />
              </div>
              <span className="text-xl font-bold text-gray-900">npc</span>
            </div>
            
            {/* Desktop Navigation */}
            <div className="hidden md:flex items-center space-x-8">
              <a href="#services" className="text-gray-700 hover:text-black transition-colors">Services</a>
              <a href="#about" className="text-gray-700 hover:text-black transition-colors">About</a>
              <button 
                onClick={() => navigate('/contact')}
                className="text-gray-700 hover:text-black transition-colors bg-transparent border-none cursor-pointer"
              >
                Contact
              </button>
            </div>

            {/* Desktop Auth Buttons */}
            <div className="hidden md:flex items-center space-x-4">
              {isAuthenticated ? (
                <Link
                  to="/dashboard"
                  className="bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition-colors"
                >
                  Dashboard
                </Link>
              ) : (
                <>
                  <Link
                    to="/otp-login"
                    className="text-gray-700 hover:text-black transition-colors"
                  >
                    Login
                  </Link>
                  <button
                    onClick={handleGetStarted}
                    className="bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition-colors"
                  >
                    Get Started
                  </button>
                </>
              )}
            </div>

            {/* Mobile Menu Button */}
            <div className="md:hidden">
              <button
                onClick={() => navigate('/otp-login')}
                className="text-gray-700 p-2 rounded-lg hover:bg-black/10 transition-colors"
              >
                <FiUser className="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="relative min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
        <div className="container mx-auto px-4 relative z-10">
          <div className="text-center max-w-4xl mx-auto">
            <motion.h1
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8 }}
              className="text-2xl sm:text-3xl md:text-4xl lg:text-6xl font-bold text-gray-900 mb-4 md:mb-6 px-4"
            >
              Professional <span className="text-black">Home Facility</span> Services
            </motion.h1>
            
            <motion.p
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
              className="text-base sm:text-lg md:text-xl text-gray-700 mb-6 md:mb-8 max-w-2xl mx-auto px-4"
            >
              Trusted, on-time, and hassle-free solutions for every corner of your home. 
              Same-day appointments available.
            </motion.p>

            <motion.div
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.4 }}
              className="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center px-4"
            >
              <button
                onClick={handleGetStarted}
                className="bg-black text-white px-6 sm:px-8 py-3 sm:py-4 rounded-lg text-base sm:text-lg font-semibold hover:bg-gray-800 transition-colors flex items-center justify-center"
              >
                Book Service Now
                <FiArrowRight className="ml-2" />
              </button>
              <button 
                onClick={() => {
                  if (isAuthenticated) {
                    const userRole = localStorage.getItem('userRole');
                    if (userRole === 'Admin') {
                      navigate('/admin-dashboard');
                    } else if (userRole === 'Technician') {
                      navigate('/technician-dashboard');
                    } else {
                      navigate('/dashboard'); // User dashboard
                    }
                  } else {
                    navigate('/otp-login');
                  }
                }}
                className="border-2 border-black text-black px-6 sm:px-8 py-3 sm:py-4 rounded-lg text-base sm:text-lg font-semibold hover:bg-black hover:text-white transition-colors"
              >
                Learn More
              </button>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="about" className="py-12 sm:py-16 md:py-20 bg-white">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12 sm:mb-16">
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4">Why Choose NPC?</h2>
            <p className="text-base sm:text-lg md:text-xl text-gray-600 max-w-2xl mx-auto px-4">
              We provide Professional Home Facility Services with a focus on quality, safety, and customer satisfaction.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
            {features.map((feature, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 30 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.1 }}
                viewport={{ once: true }}
                className="text-center p-6 rounded-lg hover:shadow-lg transition-shadow"
              >
                <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-black">
                  {feature.icon}
                </div>
                <h3 className="text-xl font-semibold text-gray-900 mb-2">{feature.title}</h3>
                <p className="text-gray-600">{feature.description}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Services Section */}
      <section id="services" className="py-12 sm:py-16 md:py-20 bg-gray-50">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12 sm:mb-16">
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4">Our Services</h2>
            <p className="text-base sm:text-lg md:text-xl text-gray-600 max-w-2xl mx-auto px-4">
              Comprehensive pest control solutions for residential and commercial properties.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            {services.map((service: any, index: number) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, x: index % 2 === 0 ? -30 : 30 }}
                whileInView={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.6 }}
                viewport={{ once: true }}
                className="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow"
              >
                 <div className="h-48 bg-gradient-to-r from-gray-400 to-gray-600 flex items-center justify-center overflow-hidden">
                   <img 
                     src={getImageUrl(service.image_path || service.image || service.service_image) || '/images/general-pest.jpg'} 
                     alt={service.service_name || service.name}
                     className="w-full h-full object-cover"
                     onError={(e) => {
                       // Fallback to shield icon if image fails to load
                       e.currentTarget.style.display = 'none';
                       e.currentTarget.nextElementSibling?.classList.remove('hidden');
                     }}
                   />
                   <FiShield className="w-16 h-16 text-white hidden" />
                 </div>
                <div className="p-6">
                  <h3 className="text-xl font-semibold text-gray-900 mb-2">{service.service_name || service.name}</h3>
                  <p className="text-gray-600 mb-4 line-clamp-2">{service.description || 'Professional service'}</p>
                  <button 
                    onClick={() => {
                      if (isAuthenticated) {
                        const userRole = localStorage.getItem('userRole');
                        if (userRole === 'Admin') {
                          navigate('/admin-dashboard');
                        } else if (userRole === 'Technician') {
                          navigate('/technician-dashboard');
                        } else {
                          navigate(`/service/${encodeURIComponent(service.service_name || service.name)}`); // go to service details
                        }
                      } else {
                        navigate(`/service/${encodeURIComponent(service.service_name || service.name)}`);
                      }
                    }}
                    className="text-black font-semibold hover:text-gray-800 transition-colors"
                                     >
                     View Details <FiArrowRight className="inline ml-1" />
                   </button>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Promo Banner */}
      <section className="py-12 sm:py-16">
        <div className="container mx-auto px-4">
          <div className="overflow-hidden shadow-xl flex flex-col lg:flex-row items-stretch bg-blue-50 rounded-lg">
            {/* Left copy block */}
            <div className="w-full lg:w-1/2 p-6 sm:p-8 lg:p-10 xl:p-14 flex flex-col justify-center">
              <p className="text-sm font-semibold tracking-widest text-blue-600 uppercase mb-3 sm:mb-4">
                Pest Control
              </p>
              <h3 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold text-gray-900 mb-4 sm:mb-6 leading-tight">
                Keep your home pest‑free
              </h3>
              <button
                onClick={() => navigate('/service/General%20Pest%20Control')}
                className="inline-flex items-center bg-black text-white px-4 sm:px-6 py-2 sm:py-3 rounded-xl font-semibold hover:bg-gray-800 w-max text-sm sm:text-base"
              >
                Explore now
              </button>
            </div>
            {/* Right image */}
            <div className="relative w-full lg:w-1/2 min-h-[200px] sm:min-h-[260px] lg:min-h-[380px] bg-gradient-to-br from-gray-300 to-gray-500">
              <img
                src={'/images/banner1.webp'}
                alt="Bathroom cleaning"
                className="absolute inset-0 w-full h-full object-cover"
                onError={(e) => {
                  // Hide if not available and let gradient show
                  (e.currentTarget as HTMLImageElement).style.display = 'none';
                }}
              />
            </div>
          </div>
        </div>
      </section>

      {/* Smart Savings - Offers Section */}
      <section className="py-12 sm:py-16 bg-white">
        <div className="container mx-auto px-4">
          {/* Featured Offer Ribbon */}
          {activeOffers.length > 0 && (
            <div className="mb-8 sm:mb-10 overflow-hidden rounded-[5px] border border-gray-200 bg-gradient-to-r from-black via-gray-900 to-gray-700 text-white">
              <div className="flex flex-col lg:flex-row items-stretch">
                <div className="flex-1 p-6 sm:p-8 lg:p-10">
                  <div className="inline-flex items-center px-3 py-1 rounded-full bg-white/10 text-sm mb-3 sm:mb-4">
                    <FiTag className="w-4 h-4 text-blue-400 mr-2" />
                    Smart Savings
                  </div>
                  <h3 className="text-2xl sm:text-3xl md:text-4xl font-extrabold mb-3">{activeOffers[0].type === 'percent' ? `Flat ${activeOffers[0].value}% Off` : `Save ₹${activeOffers[0].value}`}</h3>
                  <p className="text-gray-200 mb-4 sm:mb-6 max-w-2xl text-sm sm:text-base">{activeOffers[0].title} — {getOfferCopy(activeOffers[0], 0)}</p>
                  <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                    {activeOffers[0].badge && (
                      <span className="inline-flex items-center px-2 sm:px-3 py-1 rounded-full bg-white/10 text-white text-xs sm:text-sm border border-white/20">
                        {activeOffers[0].badge}
                      </span>
                    )}
                  </div>
                  <div className="mt-4 sm:mt-6 flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <button
                      onClick={() => navigate(`/service/${encodeURIComponent(activeOffers[0].serviceName)}?coupon=${activeOffers[0].code}`)}
                      className="bg-white text-black px-4 sm:px-5 py-2 sm:py-3 rounded-[5px] font-semibold hover:bg-gray-100 transition-colors inline-flex items-center justify-center text-sm sm:text-base"
                    >
                      Apply Offer <FiChevronRight className="ml-2" />
                    </button>
                    <button
                      onClick={() => copyCode(activeOffers[0].code)}
                      className="px-4 sm:px-5 py-2 sm:py-3 rounded-[5px] border border-white/30 text-white hover:bg-white/10 transition-colors inline-flex items-center justify-center text-sm sm:text-base"
                    >
                      {copiedCode === activeOffers[0].code ? (<><FiCheck className="w-4 h-4 mr-2" />Copied</>) : (<><FiCopy className="w-4 h-4 mr-2" />Copy Code: {activeOffers[0].code}</>)}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Offers Grid */}
          <div className="mb-4 sm:mb-6">
            <h3 className="text-xl sm:text-2xl font-bold text-gray-900">Exclusive Offers</h3>
            <p className="text-sm sm:text-base text-gray-600">Limited-time savings across our most booked services</p>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            {activeOffers.slice(0,3).map((offer, idx) => (
              <div key={offer.id} className="bg-white rounded-[5px] border border-gray-200 shadow-sm hover:shadow-md transition-shadow p-6">
                <div className="flex items-center mb-4">
                  <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                    <FiTag className="w-5 h-5 text-blue-600" />
                  </div>
                  <div>
                    <div className="font-semibold text-gray-900">{offer.title}</div>
                    <div className="text-sm text-gray-500">{offer.type === 'percent' ? `${offer.value}% Off` : `Save ₹${offer.value}`}</div>
                  </div>
                </div>
                <p className="text-gray-600 line-clamp-2 mb-4">{getOfferCopy(offer, idx + 1)}</p>
                <div className="flex items-center justify-between mb-3">
                  <button
                    onClick={() => copyCode(offer.code)}
                    className="inline-flex items-center px-3 py-1.5 rounded-[5px] bg-gray-100 text-gray-900 text-sm hover:bg-gray-200"
                    aria-label={`Copy code ${offer.code}`}
                  >
                    {copiedCode === offer.code ? <FiCheck className="w-4 h-4 text-blue-600" /> : <FiCopy className="w-4 h-4 text-blue-600" />}
                    <span className="ml-2 font-medium">{offer.code}</span>
                  </button>
                  <span className="text-sm text-gray-500">{offer.minOrder ? `Min. ₹${offer.minOrder}` : 'No min. order'}</span>
                </div>
                {/* No timer/progress for exclusive offers */}
                <div className="flex items-center justify-between">
                  <button
                    onClick={() => navigate(`/service/${encodeURIComponent(offer.serviceName)}?coupon=${offer.code}`)}
                    className="inline-flex items-center px-4 py-2 rounded-[5px] bg-black text-white hover:bg-gray-800"
                  >
                    Apply Offer
                    <FiChevronRight className="w-4 h-4 ml-2" />
                  </button>
                  {offer.badge && (
                    <span className="text-xs px-2 py-1 rounded-[5px] border border-gray-200 text-gray-700">{offer.badge}</span>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Reviews Carousel */}
      <section className="pt-12 sm:pt-16 pb-24 sm:pb-32 md:pb-40 bg-white">
        <div className="container mx-auto px-4">
          <div className="text-center mb-8 sm:mb-10">
            <h3 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-2">Our Reviews</h3>
            <p className="text-sm sm:text-base text-gray-600">Real experiences from homes and businesses we have served</p>
          </div>
          <div
            ref={carouselRef}
            className="relative overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-smooth max-w-full mx-auto py-4 md:py-6 min-h-[280px] md:min-h-[320px] reviews-carousel"
          >
            <div className="flex space-x-6 pr-6">
              {loopedReviews.map((r, idx) => {
                const isActive = (idx % reviews.length) === activeIndex;
                return (
                <div
                  key={idx}
                  data-review-card="true"
                  className={`bg-white p-6 flex-shrink-0 snap-center transform transition-all duration-300 h-[240px] md:h-[280px] overflow-hidden review-card ${
                    isActive
                      ? 'border-2 border-blue-500 shadow-lg scale-[1.02]'
                      : 'border border-gray-100 shadow-sm hover:shadow-md'
                  }`}
                  style={{ 
                    width: '280px', // Fixed width for consistency
                    minWidth: '280px', // Minimum width for readability
                  }}
                >
                  <div className="flex items-center space-x-4 mb-4">
                    <div className="w-12 h-12 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center text-blue-600 font-semibold">
                      {r.avatar ? (
                        <img
                          src={r.avatar}
                          alt={r.name}
                          className="w-full h-full object-cover"
                          onError={(e) => ((e.currentTarget as HTMLImageElement).style.display = 'none')}
                        />
                      ) : (
                        r.name
                          .split(' ')
                          .map((n) => n[0])
                          .slice(0, 2)
                          .join('')
                      )}
                    </div>
                    <div>
                      <div className="font-semibold text-gray-900">{r.name}</div>
                      <div className="text-sm text-gray-500">{r.role}</div>
                    </div>
                  </div>
                  {/* Stars */}
                  <div className="flex items-center space-x-1 mb-2">
                    {[1,2,3,4,5].map((s) => (
                      <svg key={s} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill={s <= (r as any).rating ? '#16a34a' : '#e5e7eb'} className="w-4 h-4">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    ))}
                  </div>
                  <p className="text-gray-700 leading-relaxed">“{r.text}”</p>
                </div>
              );})}
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-12 sm:py-16 md:py-20 bg-black">
        <div className="container mx-auto px-4 text-center">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            viewport={{ once: true }}
          >
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-white mb-3 sm:mb-4">Ready to Get Started?</h2>
            <p className="text-base sm:text-lg md:text-xl text-gray-300 mb-6 sm:mb-8 max-w-2xl mx-auto px-4">
              Book your pest control service today and enjoy a pest-free environment.
            </p>
            <button
              onClick={handleGetStarted}
              className="bg-white text-black px-6 sm:px-8 py-3 sm:py-4 rounded-lg text-base sm:text-lg font-semibold hover:bg-gray-100 transition-colors"
            >
              Book Now
            </button>
          </motion.div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-8 sm:py-12">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
            <div>
              <div className="flex items-center space-x-2 mb-4">
                <div className="w-8 h-8 bg-black rounded-lg flex items-center justify-center overflow-hidden">
                  <img 
                    src="/images/logo-npc.png" 
                    alt="NPC Pest Control Logo"
                    className="w-6 h-6 object-contain"
                    onError={(e) => {
                      // Fallback to shield icon if logo fails to load
                      e.currentTarget.style.display = 'none';
                      e.currentTarget.nextElementSibling?.classList.remove('hidden');
                    }}
                  />
                  <FiShield className="w-5 h-5 text-white hidden" />
                </div>
                <span className="text-xl font-bold">NATIONAL PEST CONTROL</span>
              </div>
              <p className="text-gray-400">
                Professional Home Facility Services for your home and business.
              </p>
            </div>
            
                         <div>
               <h3 className="text-lg font-semibold mb-4">Social Media</h3>
               <div className="flex space-x-4">
                 <a 
                   href="https://www.facebook.com/share/1B3tnsUpjT/" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   className="text-gray-400 hover:text-white transition-colors"
                   title="Follow us on Facebook"
                 >
                   <FiFacebook className="w-6 h-6" />
                 </a>
                 <a 
                   href="https://www.instagram.com/npc_pest?igsh=MXFuaHoxbmY5NjdjZA==" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   className="text-gray-400 hover:text-white transition-colors"
                   title="Follow us on Instagram"
                 >
                   <FiInstagram className="w-6 h-6" />
                 </a>
                 <a 
                   href="https://www.linkedin.com/company/npcpest/" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   className="text-gray-400 hover:text-white transition-colors"
                   title="Follow us on LinkedIn"
                 >
                   <FiLinkedin className="w-6 h-6" />
                 </a>
               </div>
             </div>
            
                         <div>
               <h3 className="text-lg font-semibold mb-4">Company</h3>
               <ul className="space-y-2 text-gray-400">
                 <li>
                   <a href="#about" className="hover:text-white transition-colors cursor-pointer">
                     About Us
                   </a>
                 </li>
                 <li>
                   <button 
                     onClick={() => {
                       if (isAuthenticated) {
                         navigate('/contact');
                       } else {
                         navigate('/otp-login');
                       }
                     }}
                     className="hover:text-white transition-colors cursor-pointer bg-transparent border-none text-left p-0"
                   >
                     Contact
                   </button>
                 </li>
                                   <li>
                    <Link to="/privacy-policy" className="hover:text-white transition-colors">
                      Privacy Policy
                    </Link>
                  </li>
               </ul>
             </div>
            
            <div>
              <h3 className="text-lg font-semibold mb-4">Contact</h3>
              <ul className="space-y-2 text-gray-400">
                <li>Phone: +91 8111043335</li>
                <li>Email: info@npc.com</li>
                                  <li className="flex flex-col">
                    <span className="whitespace-nowrap">Address: NPC, NO. 158, Murugan Kovil Street,</span>
                    <span className="ml-6">Vanashakthi Nagar, Kolather, Chennai</span>
                  </li>
              </ul>
            </div>
          </div>
          
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; 2024 NATIONAL PEST CONTROL. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default LandingPage; 