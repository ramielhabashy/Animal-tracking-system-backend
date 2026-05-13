import React, { useState, useEffect, useRef, useCallback } from 'react';
import { MaterialSymbol } from 'react-material-symbols';
import { apiFetch } from '../utils/api';
import { useI18n } from '../i18n';

const INTERVAL_OPTIONS = [
  { value: 5, label: '5s' },
  { value: 15, label: '15s' },
  { value: 30, label: '30s' },
  { value: 60, label: '60s' },
];

export default function SimulatorPage({ embedded }) {
  const { t, dir } = useI18n();
  const isRtl = dir === 'rtl';

  const [devices, setDevices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [running, setRunning] = useState(false);
  const [intervalSec, setIntervalSec] = useState(15);
  const [autoPilot, setAutoPilot] = useState({});
  const [speeds, setSpeeds] = useState({});
  const [logs, setLogs] = useState([]);
  const [devicePositions, setDevicePositions] = useState({});
  const timerRef = useRef(null);
  const logEndRef = useRef(null);

  useEffect(() => {
    fetchDevices();
    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, []);

  useEffect(() => {
    logEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [logs]);

  const addLog = useCallback((msg, type = 'info') => {
    setLogs(prev => [...prev.slice(-99), { time: new Date().toLocaleTimeString(), msg, type }]);
  }, []);

  const fetchDevices = async () => {
    setLoading(true);
    try {
      const res = await apiFetch('/api/simulator/devices');
      if (res.ok) {
        const data = await res.json();
        const list = data.data || [];
        setDevices(list);
        const pos = {};
        const spd = {};
        const ap = {};
        list.forEach(d => {
          pos[d.id] = { lat: parseFloat(d.gps_lat) || 24.4539, lng: parseFloat(d.gps_lng) || 54.3773 };
          spd[d.id] = 3;
          ap[d.id] = false;
        });
        setDevicePositions(pos);
        setSpeeds(spd);
        setAutoPilot(ap);
        addLog(`Loaded ${list.length} devices with animals`, 'success');
      }
    } catch (e) {
      addLog('Failed to load devices: ' + e.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const nudgeDevice = async (deviceId, dLat, dLng) => {
    const pos = devicePositions[deviceId];
    if (!pos) return;
    const newLat = pos.lat + dLat;
    const newLng = pos.lng + dLng;
    setDevicePositions(prev => ({ ...prev, [deviceId]: { lat: newLat, lng: newLng } }));
    try {
      const res = await apiFetch('/api/simulator/move', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ device_id: deviceId, latitude: newLat, longitude: newLng, speed: speeds[deviceId] || 3, heading: dLat === 0 && dLng > 0 ? 90 : dLat === 0 && dLng < 0 ? 270 : dLat > 0 && dLng === 0 ? 0 : dLat < 0 && dLng === 0 ? 180 : 45 }),
      });
      if (res.ok) {
        const data = await res.json();
        const device = devices.find(d => d.id === deviceId);
        addLog(`${device?.name || 'Device ' + deviceId} moved → ${newLat.toFixed(5)}, ${newLng.toFixed(5)}${data.alert_triggered ? ' ⚠️ ' + data.alert_type : ''}`, 'success');
      }
    } catch (e) {
      addLog(`Move failed for device ${deviceId}: ${e.message}`, 'error');
    }
  };

  const randomNudge = async (deviceId) => {
    const step = 0.0005 + Math.random() * 0.002;
    const heading = Math.random() * 360;
    const rad = heading * (Math.PI / 180);
    const dLat = step * Math.cos(rad);
    const dLng = step * Math.sin(rad);
    await nudgeDevice(deviceId, dLat, dLng);
  };

  const generateMoves = () => {
    const moves = [];
    Object.keys(autoPilot).forEach(id => {
      if (!autoPilot[id]) return;
      const pos = devicePositions[id];
      if (!pos) return;
      const speed = speeds[id] || 3;
      const step = speed * 0.0003;
      const heading = Math.random() * 360;
      const rad = heading * (Math.PI / 180);
      moves.push({
        device_id: parseInt(id),
        latitude: pos.lat + step * Math.cos(rad),
        longitude: pos.lng + step * Math.sin(rad),
        speed: speed,
        heading: heading,
      });
    });
    return moves;
  };

  const tick = async () => {
    const moves = generateMoves();
    if (moves.length === 0) {
      addLog('Tick: no devices in auto-pilot', 'info');
      return;
    }
    try {
      const res = await apiFetch('/api/simulator/batch', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ moves }),
      });
      if (res.ok) {
        const data = await res.json();
        const results = data.results || [];
        const newPos = { ...devicePositions };
        let alertCount = 0;
        results.forEach((r, i) => {
          if (r.success) {
            newPos[r.device_id] = { lat: moves[i].latitude, lng: moves[i].longitude };
            if (r.alert_triggered) alertCount++;
          }
        });
        setDevicePositions(newPos);
        addLog(`Tick: moved ${results.filter(r => r.success).length} devices${alertCount ? `, ${alertCount} alerts triggered` : ''}`, 'success');
      }
    } catch (e) {
      addLog(`Batch tick failed: ${e.message}`, 'error');
    }
  };

  const startSimulation = () => {
    if (timerRef.current) clearInterval(timerRef.current);
    setRunning(true);
    addLog(`Simulation started (every ${intervalSec}s)`, 'info');
    tick();
    timerRef.current = setInterval(tick, intervalSec * 1000);
  };

  const stopSimulation = () => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
      timerRef.current = null;
    }
    setRunning(false);
    addLog('Simulation stopped', 'info');
  };

  const toggleAutoPilot = (deviceId) => {
    setAutoPilot(prev => ({ ...prev, [deviceId]: !prev[deviceId] }));
  };

  const handleSpeedChange = (deviceId, val) => {
    setSpeeds(prev => ({ ...prev, [deviceId]: parseInt(val) }));
  };

  const clearLogs = () => setLogs([]);

  const canRun = Object.values(autoPilot).some(Boolean);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin w-8 h-8 border-4 border-[#002819] border-t-transparent rounded-full" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {!embedded && (
        <div className={`flex items-center justify-between flex-wrap gap-4 ${isRtl ? 'flex-row-reverse' : ''}`}>
          <div>
            <h1 className="text-3xl font-bold text-[#002819]">Device Simulator</h1>
            <p className="text-[#404943] mt-1">Simulate device movement to test map, tracking, and geofences</p>
          </div>
        </div>
      )}
      <div className={`flex items-center justify-between flex-wrap gap-4 ${isRtl ? 'flex-row-reverse' : ''}`}>
        <div className="flex items-center gap-3">
          <select
            value={intervalSec}
            onChange={e => setIntervalSec(parseInt(e.target.value))}
            disabled={running}
            className="px-3 py-2 rounded-xl bg-[#F4F4EF] text-[#404943] text-sm font-medium border-0 focus:ring-2 focus:ring-[#D4AF37]"
          >
            {INTERVAL_OPTIONS.map(opt => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
          {running ? (
            <button
              onClick={stopSimulation}
              className="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold text-sm flex items-center gap-2 hover:bg-red-700 transition"
            >
              <MaterialSymbol icon="stop" size={18} />
              Stop
            </button>
          ) : (
            <button
              onClick={startSimulation}
              disabled={!canRun}
              className={`px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 transition ${
                canRun ? 'bg-gradient-to-br from-[#002819] to-[#06402B] text-white shadow-lg shadow-[#002819]/20 hover:opacity-90' : 'bg-[#E3E3DE] text-[#717973] cursor-not-allowed'
              }`}
            >
              <MaterialSymbol icon="play_arrow" size={18} />
              Start All
            </button>
          )}
        </div>
      </div>

      {devices.length === 0 ? (
        <div className="bg-[#F4F4EF] rounded-2xl p-12 text-center">
          <MaterialSymbol icon="sensors_off" size={48} className="text-[#717973] mx-auto mb-4" />
          <h2 className="text-xl font-bold text-[#404943]">No devices found</h2>
          <p className="text-[#717973] mt-2">Assign devices to animals first, then return here to simulate movement.</p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {devices.map(device => {
              const pos = devicePositions[device.id] || { lat: 0, lng: 0 };
              const isAuto = autoPilot[device.id] || false;
              const speed = speeds[device.id] || 3;
              return (
                <div key={device.id} className={`bg-white rounded-2xl p-5 shadow-sm border transition ${isAuto ? 'border-[#D4AF37] ring-1 ring-[#D4AF37]/30' : 'border-[#eeeee9]'}`}>
                  <div className={`flex items-center justify-between mb-3 ${isRtl ? 'flex-row-reverse' : ''}`}>
                    <div className="flex items-center gap-3">
                      <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${isAuto ? 'bg-[#D4AF37]/20 text-[#735C00]' : 'bg-[#F4F4EF] text-[#404943]'}`}>
                        <MaterialSymbol icon={device.type === 'collar' ? 'watch' : 'sensors'} size={20} />
                      </div>
                      <div>
                        <p className="font-bold text-[#002819] text-sm">{device.name || device.device_id}</p>
                        <p className="text-xs text-[#717973]">{device.animal?.animal_id || device.animal?.name || 'Unknown animal'}</p>
                      </div>
                    </div>
                    <label className="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" checked={isAuto} onChange={() => toggleAutoPilot(device.id)} className="sr-only peer" />
                      <div className="w-9 h-5 bg-[#E3E3DE] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#002819]" />
                    </label>
                  </div>

                  <div className={`flex items-center gap-4 mb-3 ${isRtl ? 'flex-row-reverse' : ''}`}>
                    <div className="flex-1">
                      <p className="text-xs text-[#717973] font-medium">Latitude</p>
                      <p className="text-sm font-mono font-bold text-[#002819]">{pos.lat.toFixed(6)}</p>
                    </div>
                    <div className="flex-1">
                      <p className="text-xs text-[#717973] font-medium">Longitude</p>
                      <p className="text-sm font-mono font-bold text-[#002819]">{pos.lng.toFixed(6)}</p>
                    </div>
                    <div className="text-right">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-bold ${device.status === 'online' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>
                        {device.status}
                      </span>
                    </div>
                  </div>

                  <div className={`flex items-center gap-2 mb-3 ${isRtl ? 'flex-row-reverse' : ''}`}>
                    <span className="text-xs text-[#717973] font-medium">Speed:</span>
                    <input
                      type="range"
                      min="1"
                      max="20"
                      value={speed}
                      onChange={e => handleSpeedChange(device.id, e.target.value)}
                      className="flex-1 h-1.5 bg-[#E3E3DE] rounded-full appearance-none cursor-pointer accent-[#002819]"
                    />
                    <span className="text-xs font-mono font-bold text-[#002819] w-8 text-right">{speed} km/h</span>
                  </div>

                  <div className={`flex gap-1.5 ${isRtl ? 'flex-row-reverse' : ''}`}>
                    <button onClick={() => nudgeDevice(device.id, 0.001, 0)} className="flex-1 px-2 py-1.5 bg-[#F4F4EF] rounded-lg text-xs font-medium text-[#404943] hover:bg-[#E3E3DE] transition flex items-center justify-center gap-1">
                      <MaterialSymbol icon="arrow_upward" size={14} /> N
                    </button>
                    <button onClick={() => nudgeDevice(device.id, -0.001, 0)} className="flex-1 px-2 py-1.5 bg-[#F4F4EF] rounded-lg text-xs font-medium text-[#404943] hover:bg-[#E3E3DE] transition flex items-center justify-center gap-1">
                      <MaterialSymbol icon="arrow_downward" size={14} /> S
                    </button>
                    <button onClick={() => nudgeDevice(device.id, 0, -0.001)} className="flex-1 px-2 py-1.5 bg-[#F4F4EF] rounded-lg text-xs font-medium text-[#404943] hover:bg-[#E3E3DE] transition flex items-center justify-center gap-1">
                      <MaterialSymbol icon="arrow_back" size={14} /> W
                    </button>
                    <button onClick={() => nudgeDevice(device.id, 0, 0.001)} className="flex-1 px-2 py-1.5 bg-[#F4F4EF] rounded-lg text-xs font-medium text-[#404943] hover:bg-[#E3E3DE] transition flex items-center justify-center gap-1">
                      <MaterialSymbol icon="arrow_forward" size={14} /> E
                    </button>
                    <button onClick={() => randomNudge(device.id)} className="flex-1 px-2 py-1.5 bg-[#D4AF37]/10 rounded-lg text-xs font-medium text-[#735C00] hover:bg-[#D4AF37]/20 transition flex items-center justify-center gap-1">
                      <MaterialSymbol icon="shuffle" size={14} /> Rand
                    </button>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="bg-white rounded-2xl shadow-sm border border-[#eeeee9] overflow-hidden">
            <div className={`flex items-center justify-between px-5 py-3 border-b border-[#eeeee9] ${isRtl ? 'flex-row-reverse' : ''}`}>
              <div className="flex items-center gap-2">
                <MaterialSymbol icon="terminal" size={18} className="text-[#717973]" />
                <span className="font-bold text-sm text-[#404943]">Activity Log</span>
                <span className="text-xs text-[#717973]">({logs.length} entries)</span>
              </div>
              <button onClick={clearLogs} className="text-xs text-red-500 hover:text-red-700 font-medium transition">Clear</button>
            </div>
            <div className="h-48 overflow-y-auto p-4 bg-[#FAF1F5] font-mono text-xs">
              {logs.length === 0 ? (
                <p className="text-[#717973] italic">No activity yet. Toggle auto-pilot on devices and start the simulation.</p>
              ) : (
                logs.map((log, i) => (
                  <div key={i} className={`mb-1 ${log.type === 'error' ? 'text-red-600' : log.type === 'success' ? 'text-emerald-700' : 'text-[#404943]'}`}>
                    <span className="text-[#717973]">[{log.time}]</span> {log.msg}
                  </div>
                ))
              )}
              <div ref={logEndRef} />
            </div>
          </div>
        </>
      )}
    </div>
  );
}
