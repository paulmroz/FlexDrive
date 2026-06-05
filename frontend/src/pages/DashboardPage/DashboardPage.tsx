import { useEffect, useState, startTransition } from 'react';
import { useNavigate } from 'react-router-dom';
import { useUserStore } from '../../entities/user/model/userStore';
import { carApi, type Car } from '../../shared/api/carApi';
import { subscriptionApi } from '../../shared/api/subscriptionApi';
import { advisorApi } from '../../shared/api/advisorApi';
import { Car as CarIcon, Bot, LogOut, Plus, Trash2, Edit2, Send, Loader2, Sparkles, Check, X, Calendar } from 'lucide-react';

interface ChatMessage {
  sender: 'user' | 'bot';
  content: string;
  suggestions?: Array<{
    carId: string;
    brand: string;
    model: string;
    reason: string;
  }>;
}

export const DashboardPage = () => {
  const { email, isAdmin, clearAuth } = useUserStore();
  const navigate = useNavigate();

  const [activeTab, setActiveTab] = useState<'fleet' | 'advisor'>('fleet');
  const [cars, setCars] = useState<Car[]>([]);
  const [isCarsLoading, setIsCarsLoading] = useState(true);

  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [editingCar, setEditingCar] = useState<Car | null>(null);

  const [addBrand, setAddBrand] = useState('');
  const [addModel, setAddModel] = useState('');
  const [addPrice, setAddPrice] = useState(0);

  const [editBrand, setEditBrand] = useState('');
  const [editModel, setEditModel] = useState('');
  const [editPrice, setEditPrice] = useState(0);
  const [editAvailable, setEditAvailable] = useState(true);

  const [isBookModalOpen, setIsBookModalOpen] = useState(false);
  const [bookingCar, setBookingCar] = useState<Car | null>(null);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [bookError, setBookError] = useState<string | null>(null);
  const [bookSuccess, setBookSuccess] = useState<string | null>(null);

  const [sessionId] = useState(() => Math.random().toString(36).substring(2, 15));
  const [chatMessages, setChatMessages] = useState<ChatMessage[]>([
    {
      sender: 'bot',
      content: 'Hello! I am your AI Fleet Advisor. Describe the type of car you are looking for, or your plans (e.g. "I want a spacious family car for a summer road trip" or "I need a fast electric car"), and I will match you with the best vehicle in our fleet!',
    },
  ]);
  const [chatInput, setChatInput] = useState('');
  const [isAiLoading, setIsAiLoading] = useState(false);

  const fetchCars = async () => {
    try {
      setIsCarsLoading(true);
      const data = await carApi.list();
      setCars(data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsCarsLoading(false);
    }
  };

  useEffect(() => {
    fetchCars();
  }, []);

  const handleLogout = () => {
    clearAuth();
    startTransition(() => {
      navigate('/login');
    });
  };

  const handleAddCar = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await carApi.add({
        brand: addBrand,
        model: addModel,
        pricePerDay: Math.round(addPrice * 100),
      });
      setIsAddModalOpen(false);
      setAddBrand('');
      setAddModel('');
      setAddPrice(0);
      fetchCars();
    } catch (err) {
      console.error(err);
    }
  };

  const handleEditCar = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingCar) return;
    try {
      await carApi.update(editingCar.id, {
        brand: editBrand,
        model: editModel,
        pricePerDay: Math.round(editPrice * 100),
        available: editAvailable,
      });
      setIsEditModalOpen(false);
      setEditingCar(null);
      fetchCars();
    } catch (err) {
      console.error(err);
    }
  };

  const handleDeleteCar = async (id: string) => {
    if (!window.confirm('Are you sure you want to delete this car?')) return;
    try {
      await carApi.remove(id);
      fetchCars();
    } catch (err) {
      console.error(err);
    }
  };

  const handleBookCar = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!bookingCar) return;
    setBookError(null);
    setBookSuccess(null);

    try {
      const response = await subscriptionApi.create({
        carId: bookingCar.id,
        startDate,
        endDate: endDate || null,
      });

      if (response.paymentUrl) {
        window.location.href = response.paymentUrl;
      } else {
        setBookSuccess('Subscription created successfully!');
        setTimeout(() => {
          setIsBookModalOpen(false);
          setBookingCar(null);
          setStartDate('');
          setEndDate('');
          setBookSuccess(null);
          fetchCars();
        }, 2000);
      }
    } catch (err: any) {
      setBookError(err.response?.data?.detail || err.response?.data?.message || 'Failed to create subscription.');
    }
  };

  const handleSendChatMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!chatInput.trim() || isAiLoading) return;

    const userPrompt = chatInput.trim();
    setChatMessages((prev) => [...prev, { sender: 'user', content: userPrompt }]);
    setChatInput('');
    setIsAiLoading(true);

    const sseUrl = advisorApi.getSseUrl(sessionId);
    const eventSource = new EventSource(sseUrl);

    eventSource.onmessage = (event) => {
      try {
        const payload = JSON.parse(event.data);
        if (payload.status === 'completed') {
          const suggestions = payload.suggestions.map((s: any) => {
            const matchedCar = cars.find((c) => c.id === s.carId);
            return {
              carId: s.carId,
              brand: matchedCar ? matchedCar.brand : 'Unknown',
              model: matchedCar ? matchedCar.model : 'Car',
              reason: s.reason,
            };
          });

          setChatMessages((prev) => [
            ...prev,
            {
              sender: 'bot',
              content: suggestions.length > 0
                ? "Based on your vibes, here are my top recommendations from our fleet:"
                : "I couldn't find any available cars matching your exact description, but please browse our fleet list to see other options!",
              suggestions,
            },
          ]);
        }
      } catch (err) {
        console.error(err);
      } finally {
        eventSource.close();
        setIsAiLoading(false);
      }
    };

    eventSource.onerror = () => {
      setChatMessages((prev) => [
        ...prev,
        { sender: 'bot', content: 'Connection timed out or failed. Please try again.' },
      ]);
      eventSource.close();
      setIsAiLoading(false);
    };

    try {
      await advisorApi.submitMessage(sessionId, { message: userPrompt });
    } catch (err) {
      console.error(err);
      eventSource.close();
      setIsAiLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 flex flex-col text-slate-100">
      <header className="bg-slate-900/50 backdrop-blur-xl border-b border-slate-800 px-6 py-4 flex items-center justify-between sticky top-0 z-40">
        <div className="flex items-center gap-3">
          <div className="p-2.5 bg-gradient-to-tr from-violet-600 to-indigo-600 rounded-xl shadow-lg shadow-indigo-500/25">
            <CarIcon className="w-6 h-6 text-white" />
          </div>
          <div>
            <h1 className="text-xl font-bold tracking-tight text-white leading-none">DriveAgency</h1>
            <span className="text-xs text-slate-400">FlexDrive Portal</span>
          </div>
        </div>

        <div className="flex items-center gap-6">
          <div className="hidden sm:flex flex-col items-end">
            <span className="text-sm font-medium text-slate-200">{email}</span>
            <span className="text-xs text-slate-400 flex items-center gap-1">
              <span className={`inline-block w-1.5 h-1.5 rounded-full ${isAdmin ? 'bg-amber-400' : 'bg-emerald-400'}`} />
              {isAdmin ? 'Administrator' : 'Client Account'}
            </span>
          </div>

          <button
            onClick={handleLogout}
            className="flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-sm font-medium rounded-xl transition-all shadow-md"
          >
            <LogOut className="w-4 h-4" />
            <span className="hidden sm:inline">Log Out</span>
          </button>
        </div>
      </header>

      <main className="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
        <div className="flex border-b border-slate-800">
          <button
            onClick={() => setActiveTab('fleet')}
            className={`flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-all ${
              activeTab === 'fleet'
                ? 'border-violet-500 text-violet-400'
                : 'border-transparent text-slate-400 hover:text-slate-200'
            }`}
          >
            <CarIcon className="w-4 h-4" />
            Browse Fleet
          </button>
          <button
            onClick={() => setActiveTab('advisor')}
            className={`flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-all ${
              activeTab === 'advisor'
                ? 'border-violet-500 text-violet-400'
                : 'border-transparent text-slate-400 hover:text-slate-200'
            }`}
          >
            <Bot className="w-4 h-4" />
            AI Fleet Advisor
            <span className="flex h-2 w-2 relative">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2 w-2 bg-violet-500"></span>
            </span>
          </button>
        </div>

        {activeTab === 'fleet' && (
          <div className="space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-2xl font-bold text-white">Our Car Fleet</h2>
                <p className="text-sm text-slate-400">Choose your vehicle and start your subscription today</p>
              </div>

              {isAdmin && (
                <button
                  onClick={() => setIsAddModalOpen(true)}
                  className="flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium rounded-xl shadow-lg shadow-violet-500/20 hover:shadow-violet-500/30 transition-all"
                >
                  <Plus className="w-4 h-4" />
                  Add New Vehicle
                </button>
              )}
            </div>

            {isCarsLoading ? (
              <div className="flex flex-col items-center justify-center py-20 gap-3">
                <Loader2 className="w-10 h-10 animate-spin text-violet-500" />
                <p className="text-sm text-slate-400">Loading fleet database...</p>
              </div>
            ) : cars.length === 0 ? (
              <div className="text-center py-20 bg-slate-900/25 border border-dashed border-slate-800 rounded-2xl">
                <CarIcon className="w-12 h-12 text-slate-600 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-slate-300">No cars found</h3>
                <p className="text-sm text-slate-500 mt-1">Check back later or add a vehicle to the database.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {cars.map((car) => (
                  <div
                    key={car.id}
                    className="bg-slate-900/40 border border-slate-800/80 rounded-2xl overflow-hidden flex flex-col hover:border-slate-700/80 transition-all group"
                  >
                    <div className="h-44 bg-gradient-to-br from-indigo-950/50 to-slate-900 flex items-center justify-center relative p-6 border-b border-slate-850">
                      <div className="absolute top-4 right-4">
                        <span
                          className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold shadow-md ${
                            car.available
                              ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/25'
                              : 'bg-rose-500/10 text-rose-400 border border-rose-500/25'
                          }`}
                        >
                          <span className={`w-1.5 h-1.5 rounded-full ${car.available ? 'bg-emerald-400' : 'bg-rose-400'}`} />
                          {car.available ? 'Available' : 'Booked'}
                        </span>
                      </div>
                      <CarIcon className="w-20 h-20 text-slate-800 group-hover:scale-110 transition-transform duration-300" />
                    </div>

                    <div className="p-6 flex-1 flex flex-col justify-between">
                      <div className="mb-6">
                        <span className="text-xs font-medium text-violet-400 tracking-wider uppercase">{car.brand}</span>
                        <h3 className="text-xl font-bold text-white mt-1">{car.model}</h3>
                        <div className="mt-4 flex items-baseline gap-1">
                          <span className="text-2xl font-extrabold text-white">${(car.pricePerDay / 100).toFixed(2)}</span>
                          <span className="text-xs text-slate-400">/ day</span>
                        </div>
                      </div>

                      <div className="flex items-center gap-2">
                        {isAdmin ? (
                          <>
                            <button
                              onClick={() => {
                                setEditingCar(car);
                                setEditBrand(car.brand);
                                setEditModel(car.model);
                                setEditPrice(car.pricePerDay / 100);
                                setEditAvailable(car.available);
                                setIsEditModalOpen(true);
                              }}
                              className="flex-1 flex items-center justify-center gap-2 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-medium rounded-xl border border-slate-750 transition-all"
                            >
                              <Edit2 className="w-3.5 h-3.5" />
                              Edit
                            </button>
                            <button
                              onClick={() => handleDeleteCar(car.id)}
                              className="px-3 py-2.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 rounded-xl transition-all border border-rose-500/10"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </>
                        ) : (
                          <button
                            onClick={() => {
                              setBookingCar(car);
                              setIsBookModalOpen(true);
                            }}
                            disabled={!car.available}
                            className="w-full py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white text-xs font-semibold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-indigo-500/10"
                          >
                            Subscribe Now
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {activeTab === 'advisor' && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1">
            <div className="lg:col-span-2 flex flex-col bg-slate-900/30 border border-slate-800 rounded-2xl overflow-hidden h-[600px] shadow-2xl">
              <div className="px-6 py-4 bg-slate-900/60 border-b border-slate-800 flex items-center gap-3">
                <div className="p-2 bg-violet-500/10 border border-violet-500/20 text-violet-400 rounded-lg">
                  <Sparkles className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="font-bold text-white text-sm">Advisor Chat</h3>
                  <span className="text-xs text-slate-400">Powered by OpenAI Platform</span>
                </div>
              </div>

              <div className="flex-1 p-6 overflow-y-auto space-y-6">
                {chatMessages.map((msg, index) => (
                  <div key={index} className={`flex gap-3 max-w-[85%] ${msg.sender === 'user' ? 'ml-auto flex-row-reverse' : ''}`}>
                    <div className={`p-2.5 rounded-xl self-start ${
                      msg.sender === 'user'
                        ? 'bg-violet-600 text-white'
                        : 'bg-slate-900/80 border border-slate-800 text-slate-200'
                    }`}>
                      {msg.sender === 'bot' ? <Bot className="w-5 h-5" /> : <User className="w-5 h-5" />}
                    </div>

                    <div className="space-y-4">
                      <div className={`p-4 rounded-2xl ${
                        msg.sender === 'user'
                          ? 'bg-violet-500/10 border border-violet-500/25 text-violet-200'
                          : 'bg-slate-900/40 border border-slate-800/80 text-slate-300'
                      }`}>
                        <p className="text-sm leading-relaxed whitespace-pre-line">{msg.content}</p>
                      </div>

                      {msg.suggestions && msg.suggestions.length > 0 && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                          {msg.suggestions.map((s) => (
                            <div key={s.carId} className="p-4 bg-slate-900/80 border border-slate-800 rounded-xl hover:border-violet-500/30 transition-all flex flex-col justify-between">
                              <div>
                                <span className="text-xs font-semibold text-violet-400 uppercase tracking-wider">{s.brand}</span>
                                <h4 className="font-bold text-white text-base mt-0.5">{s.model}</h4>
                                <p className="text-xs text-slate-400 mt-2 leading-relaxed">{s.reason}</p>
                              </div>
                              <button
                                onClick={() => {
                                  const matched = cars.find((c) => c.id === s.carId);
                                  if (matched) {
                                    setBookingCar(matched);
                                    setIsBookModalOpen(true);
                                  }
                                }}
                                className="w-full mt-4 py-2 bg-violet-600/10 hover:bg-violet-600 text-violet-400 hover:text-white text-xs font-medium rounded-lg border border-violet-500/10 hover:border-transparent transition-all"
                              >
                                View / Book
                              </button>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>
                ))}

                {isAiLoading && (
                  <div className="flex gap-3 max-w-[85%]">
                    <div className="p-2.5 bg-slate-900/80 border border-slate-800 text-slate-200 rounded-xl self-start">
                      <Bot className="w-5 h-5 animate-pulse" />
                    </div>
                    <div className="p-4 bg-slate-900/40 border border-slate-800/80 text-slate-300 rounded-2xl flex items-center gap-2">
                      <Loader2 className="w-4 h-4 animate-spin text-violet-500" />
                      <span className="text-xs text-slate-400 font-medium">Advisor is analyzing our fleet...</span>
                    </div>
                  </div>
                )}
              </div>

              <form onSubmit={handleSendChatMessage} className="p-4 bg-slate-900/20 border-t border-slate-800 flex gap-2">
                <input
                  type="text"
                  value={chatInput}
                  onChange={(e) => setChatInput(e.target.value)}
                  placeholder="Ask advisor for a recommendation..."
                  disabled={isAiLoading}
                  className="flex-1 px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition-all disabled:opacity-50"
                />
                <button
                  type="submit"
                  disabled={isAiLoading || !chatInput.trim()}
                  className="px-4 py-3 bg-violet-600 hover:bg-violet-500 text-white font-medium rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-violet-500/10"
                >
                  <Send className="w-4 h-4" />
                </button>
              </form>
            </div>

            <div className="bg-slate-900/20 border border-slate-800 p-6 rounded-2xl space-y-6 self-start">
              <div>
                <h3 className="text-lg font-bold text-white flex items-center gap-2">
                  <Sparkles className="w-5 h-5 text-violet-400" />
                  Fleet Hints
                </h3>
                <p className="text-xs text-slate-400 mt-1 leading-relaxed">
                  Our Fleet Advisor matches you with actual available cars in the database using prompt instructions. Try asking for:
                </p>
              </div>

              <ul className="space-y-3">
                <li
                  onClick={() => !isAiLoading && setChatInput("I want a fast electric sports car")}
                  className="p-3 bg-slate-900/50 hover:bg-slate-900 border border-slate-800/80 hover:border-slate-700/80 rounded-xl text-xs text-slate-300 hover:text-white cursor-pointer transition-all leading-normal"
                >
                  "I want a fast electric sports car"
                </li>
                <li
                  onClick={() => !isAiLoading && setChatInput("Looking for a cheap daily commuter under $30/day")}
                  className="p-3 bg-slate-900/50 hover:bg-slate-900 border border-slate-800/80 hover:border-slate-700/80 rounded-xl text-xs text-slate-300 hover:text-white cursor-pointer transition-all leading-normal"
                >
                  "Looking for a cheap daily commuter under $30/day"
                </li>
                <li
                  onClick={() => !isAiLoading && setChatInput("Need a spacious luxury SUV for family travel")}
                  className="p-3 bg-slate-900/50 hover:bg-slate-900 border border-slate-800/80 hover:border-slate-700/80 rounded-xl text-xs text-slate-300 hover:text-white cursor-pointer transition-all leading-normal"
                >
                  "Need a spacious luxury SUV for family travel"
                </li>
              </ul>
            </div>
          </div>
        )}
      </main>

      {isAddModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
          <div className="bg-slate-900 border border-slate-800 w-full max-w-md p-6 rounded-2xl shadow-2xl relative">
            <button
              onClick={() => setIsAddModalOpen(false)}
              className="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors"
            >
              <X className="w-5 h-5" />
            </button>

            <h3 className="text-xl font-bold text-white mb-6">Add New Vehicle</h3>

            <form onSubmit={handleAddCar} className="space-y-4">
              <div className="space-y-1">
                <label className="block text-xs font-medium text-slate-400">Brand</label>
                <input
                  type="text"
                  required
                  value={addBrand}
                  onChange={(e) => setAddBrand(e.target.value)}
                  placeholder="e.g. Tesla, Porsche"
                  className="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-505 transition-all"
                />
              </div>

              <div className="space-y-1">
                <label className="block text-xs font-medium text-slate-400">Model</label>
                <input
                  type="text"
                  required
                  value={addModel}
                  onChange={(e) => setAddModel(e.target.value)}
                  placeholder="e.g. Model S, 911 GT3"
                  className="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-505 transition-all"
                />
              </div>

              <div className="space-y-1">
                <label className="block text-xs font-medium text-slate-400">Price Per Day ($ USD)</label>
                <input
                  type="number"
                  required
                  min="1"
                  value={addPrice || ''}
                  onChange={(e) => setAddPrice(parseFloat(e.target.value))}
                  placeholder="e.g. 59.90"
                  className="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-505 transition-all"
                />
              </div>

              <button
                type="submit"
                className="w-full mt-4 py-3 bg-violet-600 hover:bg-violet-500 text-white font-medium rounded-xl shadow-lg transition-all"
              >
                Create Vehicle
              </button>
            </form>
          </div>
        </div>
      )}

      {isEditModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
          <div className="bg-slate-900 border border-slate-800 w-full max-w-md p-6 rounded-2xl shadow-2xl relative">
            <button
              onClick={() => {
                setIsEditModalOpen(false);
                setEditingCar(null);
              }}
              className="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors"
            >
              <X className="w-5 h-5" />
            </button>

            <h3 className="text-xl font-bold text-white mb-6">Edit Vehicle</h3>

            <form onSubmit={handleEditCar} className="space-y-4">
              <div className="space-y-1">
                <label className="block text-xs font-medium text-slate-400">Brand</label>
                <input
                  type="text"
                  required
                  value={editBrand}
                  onChange={(e) => setEditBrand(e.target.value)}
                  className="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-505 transition-all"
                />
              </div>

              <div className="space-y-1">
                <label className="block text-xs font-medium text-slate-400">Model</label>
                <input
                  type="text"
                  required
                  value={editModel}
                  onChange={(e) => setEditModel(e.target.value)}
                  className="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-505 transition-all"
                />
              </div>

              <div className="space-y-1">
                <label className="block text-xs font-medium text-slate-400">Price Per Day ($ USD)</label>
                <input
                  type="number"
                  required
                  min="1"
                  value={editPrice || ''}
                  onChange={(e) => setEditPrice(parseFloat(e.target.value))}
                  className="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-505 transition-all"
                />
              </div>

              <div className="flex items-center gap-3 py-2">
                <input
                  type="checkbox"
                  id="editAvailable"
                  checked={editAvailable}
                  onChange={(e) => setEditAvailable(e.target.checked)}
                  className="w-4 h-4 text-violet-600 border-slate-800 rounded focus:ring-violet-500/50 focus:ring-offset-slate-900 focus:ring-2"
                />
                <label htmlFor="editAvailable" className="text-sm font-medium text-slate-300">
                  Available for subscription bookings
                </label>
              </div>

              <button
                type="submit"
                className="w-full mt-4 py-3 bg-violet-600 hover:bg-violet-500 text-white font-medium rounded-xl shadow-lg transition-all"
              >
                Save Changes
              </button>
            </form>
          </div>
        </div>
      )}

      {isBookModalOpen && bookingCar && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
          <div className="bg-slate-900 border border-slate-800 w-full max-w-md p-6 rounded-2xl shadow-2xl relative">
            <button
              onClick={() => {
                setIsBookModalOpen(false);
                setBookingCar(null);
                setStartDate('');
                setEndDate('');
                setBookError(null);
                setBookSuccess(null);
              }}
              className="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors"
            >
              <X className="w-5 h-5" />
            </button>

            <div className="mb-6">
              <span className="text-xs font-semibold text-violet-400 tracking-wider uppercase">{bookingCar.brand}</span>
              <h3 className="text-xl font-bold text-white mt-0.5">Subscribe to {bookingCar.model}</h3>
              <p className="text-xs text-slate-400 mt-1">
                Enter your desired start and optional end dates to book.
              </p>
            </div>

            <form onSubmit={handleBookCar} className="space-y-4">
              <div className="space-y-1">
                <label className="block text-xs font-medium text-slate-400">Start Date</label>
                <div className="relative">
                  <Calendar className="w-4 h-4 text-slate-500 absolute left-3 top-3.5" />
                  <input
                    type="date"
                    required
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-505 transition-all"
                  />
                </div>
              </div>

              <div className="space-y-1">
                <label className="block text-xs font-medium text-slate-400">End Date (Optional)</label>
                <div className="relative">
                  <Calendar className="w-4 h-4 text-slate-500 absolute left-3 top-3.5" />
                  <input
                    type="date"
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    className="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-505 transition-all"
                  />
                </div>
              </div>

              <div className="pt-2">
                <div className="flex justify-between items-center bg-slate-950 p-4 border border-slate-850 rounded-xl">
                  <span className="text-xs text-slate-400">Price rate:</span>
                  <span className="text-sm font-bold text-white">${(bookingCar.pricePerDay / 100).toFixed(2)} / day</span>
                </div>
              </div>

              {bookError && (
                <div className="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm rounded-xl">
                  {bookError}
                </div>
              )}

              {bookSuccess && (
                <div className="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-xl flex items-center justify-center gap-2">
                  <Check className="w-4 h-4 text-emerald-400" />
                  {bookSuccess}
                </div>
              )}

              <button
                type="submit"
                disabled={!startDate}
                className="w-full mt-4 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Proceed to Checkout
              </button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
