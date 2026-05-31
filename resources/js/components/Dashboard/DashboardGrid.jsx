import React, { useState, useCallback } from 'react';
import {
  DndContext,
  closestCenter,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  SortableContext,
  arrayMove,
  rectSortingStrategy,
} from '@dnd-kit/sortable';
import { MaterialSymbol } from 'react-material-symbols';
import { useI18n } from '../../i18n';
import { useAuth } from '../../context/AuthContext';
import DashboardWidget from './DashboardWidget';
import { getWidgetsForRole, reorderWidgets, resetLayout } from './dashboardConfig';
import StatsCardsWidget from './widgets/StatsCardsWidget';
import MapWidget from './widgets/MapWidget';
import AlertsPanelWidget from './widgets/AlertsPanelWidget';
import QuickActionsWidget from './widgets/QuickActionsWidget';
import SubscriptionOverviewWidget from './widgets/SubscriptionOverviewWidget';
import TierDistributionWidget from './widgets/TierDistributionWidget';
import OwnerOverviewWidget from './widgets/OwnerOverviewWidget';
import ChartsWidget from './widgets/ChartsWidget';
import MedicalOverviewWidget from './widgets/MedicalOverviewWidget';
import TasksWidget from './widgets/TasksWidget';

const widgetComponents = {
  statsCards: StatsCardsWidget,
  map: MapWidget,
  alertsPanel: AlertsPanelWidget,
  quickActions: QuickActionsWidget,
  subscriptionOverview: SubscriptionOverviewWidget,
  tierDistribution: TierDistributionWidget,
  ownerOverview: OwnerOverviewWidget,
  chartsWidget: ChartsWidget,
  medicalOverview: MedicalOverviewWidget,
  tasksWidget: TasksWidget,
};

function getGridColsClass(gridDesktop) {
  if (gridDesktop === 12) return 'lg:col-span-12';
  if (gridDesktop === 8) return 'lg:col-span-8';
  if (gridDesktop === 6) return 'lg:col-span-6';
  if (gridDesktop === 4) return 'lg:col-span-4';
  if (gridDesktop === 3) return 'lg:col-span-3';
  return 'lg:col-span-12';
}

function getTabletColsClass(gridTablet) {
  if (gridTablet === 12) return 'md:col-span-12';
  if (gridTablet === 8) return 'md:col-span-8';
  if (gridTablet === 6) return 'md:col-span-6';
  if (gridTablet === 4) return 'md:col-span-4';
  if (gridTablet === 3) return 'md:col-span-3';
  return 'md:col-span-12';
}

export default function DashboardGrid({ dashboardData }) {
  const { t } = useI18n();
  const { user } = useAuth();
  const role = user?.role || 'Owner';

  const [widgets, setWidgets] = useState(() => getWidgetsForRole(role, t));

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } })
  );

  const handleDragEnd = useCallback((event) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;
    setWidgets((prev) => {
      const oldIndex = prev.findIndex((w) => w.id === active.id);
      const newIndex = prev.findIndex((w) => w.id === over.id);
      if (oldIndex === -1 || newIndex === -1) return prev;
      const reordered = arrayMove(prev, oldIndex, newIndex);
      reorderWidgets(role, reordered.map((w) => w.id));
      return reordered;
    });
  }, [role]);

  const handleReset = useCallback(() => {
    resetLayout(role);
    setWidgets(getWidgetsForRole(role, t));
  }, [role, t]);

  if (!widgets || widgets.length === 0) return null;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-black text-[#002819]">{t('dashboard.title')}</h2>
        <button
          onClick={handleReset}
          className="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-[#717973] hover:text-[#002819] hover:bg-[#F4F4EF] transition-all"
          title={t('dashboard.resetLayout')}
        >
          <MaterialSymbol icon="refresh" size={16} />
          {t('dashboard.resetLayout')}
        </button>
      </div>

      <DndContext
        sensors={sensors}
        collisionDetection={closestCenter}
        onDragEnd={handleDragEnd}
      >
        <SortableContext items={widgets.map((w) => w.id)} strategy={rectSortingStrategy}>
          <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
            {widgets.map((widget) => {
              const Component = widgetComponents[widget.id];
              if (!Component) return null;
              const desktopClass = getGridColsClass(widget.gridDesktop);
              const tabletClass = getTabletColsClass(widget.gridTablet);
              return (
                <div key={widget.id} className={`col-span-1 ${tabletClass} ${desktopClass}`}>
                  <DashboardWidget
                    id={widget.id}
                    title={widget.title}
                  >
                    <Component dashboardData={dashboardData} />
                  </DashboardWidget>
                </div>
              );
            })}
          </div>
        </SortableContext>
      </DndContext>
    </div>
  );
}
