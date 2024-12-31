// Type definitions for flexmonster 2.8
// Project: https://flexmonster.com/
// Definitions by:  Dima Zvazhii <https://github.com/Uaman>
//                  Ian Sadovy <https://github.com/iansadovy>
//                  Flexmonster Team (Admin) <https://github.com/flexmonsterowner>
//                  Flexmonster Team <https://github.com/flexmonsterteam>
//                  Iryna Kulchytska <https://github.com/irakulchytska>
// Definitions: https://github.com/DefinitelyTyped/DefinitelyTyped
// TypeScript Version: 2.9

export as namespace Flexmonster;

declare const Flexmonster: FlexmonsterConstructor;
export = Flexmonster;

interface FlexmonsterConstructor {
    new(params: Flexmonster.Params): Flexmonster.Pivot;
    (params: Flexmonster.Params): Flexmonster.Pivot;
}

declare namespace Flexmonster {
    interface Params {
        // params
        accessibility?: AccessibilityOptions;
        componentFolder?: string;
        container?: string | Element;
        height?: string | number;
        global?: Report;
        licenseKey?: string;
        licenseFilePath?: string;
        report?: Report | string;
        shareReportConnection?: APIClientOptions;
        toolbar?: boolean;
        width?: string | number;
        customizeAPIRequest?: (request: object) => object;
        customizeCell?: (cell: CellBuilder, data: CellData) => void;
        customizeChartElement?: (element: Element, data: ChartData | ChartLegendItemData) => void;
        customizeContextMenu?: (items: ContextMenuItem[], data: CellData | ChartData, viewType: string) => ContextMenuItem[];
        sortFieldsList?: (first: FieldsListSortingItem, second: FieldsListSortingItem, fieldsListType: string) => number;
        // events
        afterchartdraw?: () => void;
        aftergriddraw?: (param: object) => void;
        beforegriddraw?: (param: object) => void;
        beforetoolbarcreated?: (toolbar: Toolbar) => void;
        cellclick?: (cell: CellData) => void;
        celldoubleclick?: (cell: CellData) => void;
        chartclick?: (data: ChartData) => void;
        datachanged?: (param: object) => void;
        dataerror?: (event?: ErrorEvent) => void;
        datafilecancelled?: () => void;
        dataloaded?: () => void;
        drillthroughclose?: () => void;
        drillthroughopen?: (cell: CellData | ChartData) => void;
        exportcomplete?: () => void;
        exportstart?: () => void;
        fieldslistclose?: () => void;
        fieldslistopen?: () => void;
        filterclose?: () => void;
        filteropen?: (param: object) => void;
        loadingdata?: () => void;
        loadinglocalization?: () => void;
        loadingolapstructure?: () => void;
        loadingreportfile?: () => void;
        localizationerror?: () => void;
        localizationloaded?: () => void;
        olapstructureerror?: (event?: ErrorEvent) => void;
        olapstructureloaded?: () => void;
        openingreportfile?: () => void;
        printcomplete?: () => void;
        printstart?: () => void;
        querycomplete?: () => void;
        queryerror?: (event?: ErrorEvent) => void;
        ready?: () => void;
        reportchange?: () => void;
        reportcomplete?: () => void;
        reportfilecancelled?: () => void;
        reportfileerror?: () => void;
        runningquery?: () => void;
        unauthorizederror?: (done: UnauthorizedErrorHandler) => void;
        update?: () => void;
    }

    interface Pivot {
        addCalculatedMeasure(measure: Measure): void;
        addCondition(condition: ConditionalFormat): void;
        alert(options: { title?: string; message?: string; type?: string; buttons?: Array<{ label: string; handler?: () => void; }>; blocking?: boolean; }): void;
        clear(): void;
        clearFilter(hierarchyName: string): void;
        clearXMLACache(proxyUrl: string, databaseId: string, callbackHandler: ((response: object) => void) | string, cubeId: string, measuresGroupId: string,
            username?: string, password?: string): void;
        closeFieldsList(): void;
        collapseAllData(): void;
        collapseCell(axisName: string, tuple: string[], measure: string): void;
        collapseData(hierarchyName: string): void;
        connectTo(object: DataSource): void;
        customizeAPIRequest(customizeAPIRequestFunction: (request: object) => object): void;
        customizeCell(customizeCellFunction: (cell: CellBuilder, data: CellData) => void): void;
        customizeChartElement(customizeChartElementFunction: (element: Element, data: ChartData | ChartLegendItemData) => void): void;
        customizeContextMenu(customizeFunction: (items: ContextMenuItem[], data: CellData | ChartData, viewType: string) => ContextMenuItem[]): void;
        dispose(): void;
        drillDownCell(axisName: string, tuple: string[], measure: string, member: string): void;
        drillUpCell(axisName: string, tuple: string[], measure: string, member: string): void;
        expandAllData(withAllChildren?: boolean): void;
        expandCell(axisName: string, tuple: string[], measure: string): void;
        expandData(hierarchyName: string): void;
        exportTo(type: string, exportOptions?: ExportOptions, callbackHandler?: ((result: object) => void) | string): void;
        getAllConditions(): ConditionalFormat[];
        getAllHierarchies(): Hierarchy[];
        getAllHierarchiesAsync(): Promise<Hierarchy[]>;
        getAllMeasures(): Measure[];
        getCell(rowIdx: number, colIdx: number): CellData;
        getColumns(): Hierarchy[];
        getColumnsAsync(): Promise<Hierarchy[]>;
        getCondition(id: string): ConditionalFormat;
        getData(options: { slice?: Slice }, callbackHandler: ((rawData: GetDataValueObject, error?: GetDataErrorObject) => void) | string,
            updateHandler?: ((rawData: GetDataValueObject, error?: GetDataErrorObject) => void) | string): void;
        getFilter(hierarchyName: string): Filter;
        getFormat(measureName: string, aggregation?: string): Format;
        getMeasures(): Measure[];
        getMeasuresAsync(): Promise<Measure[]>;
        getMembers(hierarchyName: string, memberName: string, callbackHandler: ((members: Member[]) => void) | string): Member[];
        getMembersAsync(hierarchyName: string, memberName: string): Promise<Member[]>;
        getOptions(): Options;
        getReport(options?: GetReportOptions): Report | string;
        getReportFilters(): Hierarchy[];
        getReportFiltersAsync(): Promise<Hierarchy[]>;
        getRows(): Hierarchy[];
        getRowsAsync(): Promise<Hierarchy[]>;
        getSelectedCell(): CellData | CellData[];
        getSort(hierarchyName: string): string;
        getFlatSort(): FlatSort[];
        getXMLACatalogs(proxyURL: string, dataSource: string, callbackHandler: ((response: any) => void) | string, username?: string, password?: string): void;
        getXMLACatalogsAsync(proxyURL: string, dataSource: string): Promise<string[]>;
        getXMLACubes(proxyURL: string, dataSource: string, catalog: string, callbackHandler: ((response: any) => void) | string, username?: string, password?: string): void;
        getXMLACubesAsync(proxyURL: string, dataSource: string, catalog: string): Promise<string[]>;
        getXMLADataSources(proxyURL: string, callbackHandler: ((response: any) => void) | string, username?: string, password?: string): void;
        getXMLADataSourcesAsync(proxyURL: string): Promise<string[]>;
        getXMLAProviderName(proxyURL: string, callbackHandler: ((response: any) => void) | string, username?: string, password?: string): string;
        getXMLAProviderNameAsync(proxyURL: string): Promise<string>;
        getTableSizes(): TableSizes;
        load(url: string, requestHeaders?: { [header: string]: string | number }): void;
        off(eventType: string, handler?: ((...args: any[]) => any) | string): void;
        on(eventType: string, handler: ((...args: any[]) => any) | string): void;
        open(): void;
        openCalculatedValueEditor(uniqueName?: string, callbackHandler?: ((response: { uniqueName: string, isRemoved: boolean }) => void) | string): void;
        openFieldsList(): void;
        openFilter(hierarchyName: string): void;
        print(options?: PrintOptions): void;
        refresh(): void;
        removeAllCalculatedMeasures(): void;
        removeAllConditions(): void;
        removeCalculatedMeasure(uniqueName: string): void;
        removeCondition(id: string): void;
        removeSelection(): void;
        runQuery(slice: Slice): void;
        save(params?: {
            filename?: string, destination?: string, callbackHandler?: ((result: any, error: any) => void) | string, url?: string, embedData?: boolean, reportType?: string,
            withDefaults?: boolean, withGlobals?: boolean, requestHeaders?: { [header: string]: string | number }
        }): void;
        scrollToRow(rowIndex: number): void;
        scrollToColumn(columnIndex: number): void;
        setFilter(hierarchyName: string, filter: Filter): void;
        setFormat(format: Format, measureName: string, aggregation?: string): void;
        setOptions(options: Options): void;
        setReport(report: Report): void;
        setSort(hierarchyName: string, sortName: string, customSorting?: string[]): void;
        setFlatSort(sort: FlatSort[]): void;
        setTableSizes(tableSizes: TableSizes): void;
        shareReport(options?: APIClientOptions): Promise<string>;
        showCharts(type?: string, multiple?: boolean): void;
        showGrid(): void;
        showGridAndCharts(type?: string, position?: string, multiple?: boolean): void;
        sortFieldsList(sortingFunc: (first: FieldsListSortingItem, second: FieldsListSortingItem, fieldsListType: string) => number): void;
        sortingMethod(hierarchyName: string, compareFunction: (a: string, b: string) => number): void;
        sortValues(axisName: string, type: string, tuple?: number[], measure?: MeasureObject): void;
        toolbar: Toolbar;
        updateData(object: DataSource | object[], options?: { ignoreScroll?: boolean, ignoreSorting?: boolean, partial?: boolean }): void;
        version: string;
        amcharts?: {
            getData(options: { slice?: Slice; prepareDataFunction?: (rawData: any, options: any) => any },
                callbackHandler: ((chartData: GetDataValueObject, rawData: GetDataValueObject) => void) | string,
                updateHandler?: ((chartData: GetDataValueObject, rawData: GetDataValueObject) => void) | string): void;
            getNumberFormatPattern(format: object): string;
            getCategoryName(rawData: any): string;
            getMeasureNameByIndex(rawData: any, measureIndex: number): string;
            getNumberOfMeasures(rawData: any): number;
        };
        fusioncharts?: {
            getData(options: { type: string; slice?: Slice; prepareDataFunction?: (rawData: any, options: any) => any },
                callbackHandler: ((chartData: GetDataValueObject, rawData: GetDataValueObject) => void) | string,
                updateHandler?: ((chartData: GetDataValueObject, rawData: GetDataValueObject) => void) | string): void;
            getNumberFormat(format: object): object;
        };
        googlecharts?: {
            getData(options: { type?: string; slice?: Slice; prepareDataFunction?: (rawData: any, options: any) => any },
                callbackHandler: ((chartData: GetDataValueObject, rawData: GetDataValueObject) => void) | string,
                updateHandler?: ((chartData: GetDataValueObject, rawData: GetDataValueObject) => void) | string): void;
            getNumberFormat(format: object): object;
            getNumberFormatPattern(format: object): string;
        };
        highcharts?: {
            getData(options: { type?: string; slice?: Slice; xAxisType?: string; valuesOnly?: boolean, withDrilldown?: boolean, prepareDataFunction?: (rawData: any, options: any) => any },
                callbackHandler: ((chartData: GetDataValueObject, rawData: GetDataValueObject) => void) | string,
                updateHandler?: ((chartData: GetDataValueObject, rawData: GetDataValueObject) => void) | string): void;
            getAxisFormat(format: object): string;
            getPointXFormat(format: object): string;
            getPointYFormat(format: object): string;
            getPointZFormat(format: object): string;
        };
    }

    interface AccessibilityOptions {
        keyboardMode?: boolean;
        highContrastTheme?: string;
    }

    interface GetReportOptions {
        withGlobals?: boolean,
        withDefaults?: boolean
    }

    interface Report {
        dataSource?: DataSource;
        slice?: Slice;
        options?: Options;
        conditions?: ConditionalFormat[];
        formats?: Format[];
        tableSizes?: TableSizes;
        localization?: object | string;
        version?: string;
        creationDate?: string;
    }

    interface APIClientOptions {
        url: string | ((request: object, successHandler: (response: object | string) => void, errorHandler: (response: object | string) => void) => void);
        singleEndpoint?: boolean;
        requestHeaders?: { [header: string]: string | number };
        withCredentials?: boolean;
    }

    interface DataSource {
        type?: string;
        dataSourceType?: string;
        browseForFile?: boolean;
        catalog?: string;
        cube?: string;
        data?: object[];
        dataRootPath?: string;
        dataSourceInfo?: string;
        fieldSeparator?: string;
        thousandSeparator?: string;
        filename?: string;
        ignoreQuotedLineBreaks?: boolean;
        proxyUrl?: string;
        recordsetDelimiter?: string;
        binary?: boolean;
        roles?: string;
        localeIdentifier?: string;
        effectiveUserName?: string;
        customData?: string;
        hash?: string;
        credentials?: {
            username?: string;
            password?: string;
        },
        requestHeaders?: { [header: string]: string | number };
        subquery?: string | object;
        url?: string | ((request: object, successHandler: (response: object | string) => void, errorHandler: (response: object | string) => void) => void);
        host?: string | string[] | object; // elasticsearch
        index?: string;
        mapping?: object | string;
        withCredentials?: boolean;
        singleEndpoint?: boolean;
        useGranularityNamesForDateFilters?: boolean;
    }

    interface SliceHierarchy {
        uniqueName: string;
        caption?: string;
        dimensionName?: string;
        filter?: Filter;
        levelName?: string;
        sort?: string;
        sortOrder?: string[];
        showTotals?: boolean;
    }

    interface SliceMeasure {
        uniqueName: string;
        active?: boolean;
        aggregation?: string;
        availableAggregations?: string[];
        caption?: string;
        formula?: string;
        individual?: boolean;
        calculateNaN?: boolean;
        format?: string;
        grandTotalCaption?: string;
    }

    interface Slice {
        columns?: SliceHierarchy[];
        measures?: SliceMeasure[];
        reportFilters?: SliceHierarchy[];
        rows?: SliceHierarchy[];
        drills?: {
            drillAll?: boolean;
            columns?: Array<{ tuple: string[]; measure?: MeasureObject; }>;
            rows?: Array<{ tuple: string[]; measure?: MeasureObject; }>;
        };
        expands?: {
            expandAll?: boolean;
            columns?: Array<{ tuple: string[]; measure?: MeasureObject; }>;
            rows?: Array<{ tuple: string[]; measure?: MeasureObject; }>;
        };
        sorting?: {
            column?: { type: string; tuple: string[]; measure: MeasureObject; };
            row?: { type: string; tuple: string[]; measure: MeasureObject; };
        };
        drillThrough?: string[];
        flatOrder?: string[];
        flatSort?: FlatSort[];
    }

    interface Options {
        chart?: {
            activeMeasure?: MeasureObject | MeasureObject[];
            activeTupleIndex?: number;
            autoRange?: boolean;
            labelsHierarchy?: string;
            multipleMeasures?: boolean;
            oneLevel?: boolean;
            showFilter?: boolean;
            showLegend?: boolean;
            showLegendButton?: boolean;
            showMeasures?: boolean;
            showWarning?: boolean;
            title?: string;
            type?: string;
            showDataLabels?: boolean;
            reversedAxes?: boolean;
            showAllLabels?: boolean;
            showOneMeasureSelection?: boolean;
            position?: string;
            pieDataIndex?: string;
            axisShortNumberFormat?: boolean;
        };
        grid?: {
            showFilter?: boolean;
            showGrandTotals?: string;
            showHeaders?: boolean;
            showHierarchies?: boolean;
            showHierarchyCaptions?: boolean;
            showReportFiltersArea?: boolean;
            showTotals?: string;
            title?: string;
            type?: string;
            showAutoCalculationBar?: boolean;
            dragging?: boolean;
            grandTotalsPosition?: string;
            drillThroughMaxRows?: number;
        };
        filter?: {
            /**
             * @deprecated `timezoneOffset` was deprecated. Now filter adjusts to the time zone of the specific hierarchy.
             * The property will be eventually removed from the type definitions.
             */
            timezoneOffset?: number;
            weekOffset?: number;
            dateFormat?: string;
            liveSearch?: boolean;
        };
        allowBrowsersCache?: boolean;
        configuratorActive?: boolean;
        configuratorButton?: boolean;
        dateTimezoneOffset?: number;
        datePattern?: string;
        dateTimePattern?: string;
        defaultHierarchySortName?: string;
        drillThrough?: boolean;
        editing?: boolean;
        selectEmptyCells?: boolean;
        showAggregations?: boolean;
        showCalculatedValuesButton?: boolean;
        showDefaultSlice?: boolean;
        showMemberProperties?: boolean;
        sorting?: string | boolean;
        viewType?: string;
        showAggregationLabels?: boolean;
        useOlapFormatting?: boolean;
        defaultDateType?: string;
        timePattern?: string;
        showOutdatedDataAlert?: boolean;
        showEmptyData?: boolean;
        saveAllFormats?: boolean;
        showDrillThroughConfigurator?: boolean;
        grouping?: boolean;
        showAllFieldsDrillThrough?: boolean;
        validateFormulas?: boolean;
        showFieldListSearch?: boolean;
        strictDataTypes?: boolean;
        caseSensitiveMembers?: boolean;
        simplifyFieldListFolders?: boolean;
        validateReportFiles?: boolean;
        fieldListPosition?: string;
        showEmptyValues?: boolean | string;
        useCaptionsInCalculatedValueEditor?: boolean;
        expandExecutionTimeout?: number;
        readOnly?: boolean;
    }

    interface PrintOptions {
        header?: string;
        footer?: string;
    }

    interface Member {
        caption?: string;
        uniqueName?: string;
        hierarchyName?: string;
        children?: Member[];
        isLeaf?: boolean;
        parentMember?: string;
        properties?: any;
    }

    interface FilterProperties {
        type: string;
        members?: FilterItem[];
        quantity?: number;
        measure?: MeasureObject;
    }

    interface FilterItem {
        caption?: string;
        uniqueName?: string;
        hierarchyName?: string;
    }

    interface CellData {
        collapsed?: boolean;
        columnIndex?: number;
        columns?: object[];
        escapedLabel?: string;
        expanded?: boolean;
        drilledUp?: boolean;
        drilledDown?: boolean;
        height?: number;
        hierarchy?: Hierarchy;
        isClassicTotalRow?: boolean;
        isDrillThrough?: boolean;
        isGrandTotal?: boolean;
        isGrandTotalColumn?: boolean;
        isGrandTotalRow?: boolean;
        isTotal?: boolean;
        isTotalColumn?: boolean;
        isTotalRow?: boolean;
        label?: string;
        level?: number;
        measure?: MeasureObject;
        member?: Member;
        recordId?: string | string[];
        rowData?: CellData[];
        rowIndex?: number;
        rows?: object[];
        type?: string;
        value?: number;
        width?: number;
        x?: number;
        y?: number;
    }

    interface ExportOptions {
        filename?: string;
        destinationType?: string;
        excelSheetName?: string;
        footer?: string;
        header?: string;
        pageOrientation?: string;
        showFilters?: boolean;
        url?: string;
        useOlapFormattingInExcel?: boolean;
        useCustomizeCellForData?: boolean;
        excelExportAll?: boolean;
        requestHeaders?: { [header: string]: string | number };
        fontUrl?: string;
        alwaysEnclose?: boolean;
        pageFormat?: string;
    }

    interface Hierarchy {
        caption?: string;
        dimensionCaption?: string;
        dimensionUniqueName?: string;
        folder?: string;
        label?: string[];
        levels?: Level[];
        sort?: string;
        uniqueName?: string;
    }

    interface Filter {
        members?: string[];
        exclude?: string[];
        include?: string[];
        query?: NumberQuery | LabelQuery | DateQuery | TimeQuery | ValueQuery;
        measure?: string | MeasureObject;
    }

    interface NumberQuery {
        equal?: number;
        not_equal?: number;
        greater?: number;
        greater_equal?: number;
        less?: number;
        less_equal?: number;
        between?: number[];
        not_between?: number[];
    }

    interface LabelQuery {
        equal?: string;
        not_equal?: string;
        begin?: string;
        not_begin?: string;
        end?: string;
        not_end?: string;
        contain?: string;
        not_contain?: string;
        greater?: string;
        greater_equal?: string;
        less?: string;
        less_equal?: string;
        between?: string[];
        not_between?: string[];
    }

    interface DateQuery {
        equal?: string;
        not_equal?: string;
        before?: string;
        before_equal?: string;
        after?: string;
        after_equal?: string;
        between?: string[];
        not_between?: string[];
        last?: string;
        current?: string;
        next?: string;
    }

    interface TimeQuery {
        equal?: string;
        not_equal?: string;
        greater?: string;
        greater_equal?: string;
        less?: string;
        less_equal?: string;
        between?: string[];
        not_between?: string[];
    }

    interface ValueQuery extends NumberQuery {
        top?: number;
        bottom?: number;
    }

    interface Measure {
        active?: boolean;
        aggregation?: string;
        availableAggregations?: string[];
        availableAggregationsCaptions?: string[];
        caption?: string;
        calculated?: boolean;
        calculateNaN?: boolean;
        folder?: string;
        formula?: string;
        format?: string;
        grandTotalCaption?: string;
        individual?: boolean;
        label?: string;
        name?: string;
        uniqueName?: string;
        groupName?: string,
        type?: string;
    }

    interface MeasureObject {
        uniqueName: string;
        aggregation?: string;
    }

    interface ConditionalFormat {
        formula?: string;
        format?: Style;
        formatCSS?: string;
        row?: number;
        column?: number;
        measure?: string;
        aggregation?: string;
        hierarchy?: string;
        member?: string;
        isTotal?: boolean;
    }

    interface Style {
        color?: string;
        backgroundColor?: string;
        backgroundImage?: string;
        borderColor?: string;
        fontSize?: string;
        fontWeight?: string;
        fill?: string;
        textAlign?: string;
        fontFamily?: string;
        width?: number;
        maxWidth?: number;
        height?: number;
        maxHeight?: number;
    }

    interface Format {
        name?: string;
        thousandsSeparator?: string;
        decimalSeparator?: string;
        decimalPlaces?: number;
        maxDecimalPlaces?: number;
        maxSymbols?: number;
        negativeNumberFormat?: string;
        currencySymbol?: string;
        currencySymbolAlign?: string;
        negativeCurrencyFormat?: string;
        positiveCurrencyFormat?: string;
        nullValue?: string;
        infinityValue?: string;
        divideByZeroValue?: string;
        textAlign?: string;
        isPercent?: boolean;
        beautifyFloatingPoint?: boolean;
    }

    interface TableSizes {
        columns?: ColumnSize[];
        rows?: RowSize[];
    }

    interface ColumnSize {
        width?: number;
        idx?: number;
        tuple?: string[];
        measure?: MeasureObject;
    }

    interface RowSize {
        height?: number;
        idx?: number;
        tuple?: string[];
        measure?: MeasureObject;
    }

    interface CellBuilder {
        attr?: object;
        classes?: string[];
        style?: object;
        tag?: string;
        text?: string;
        addClass(value?: string): void;
        toHtml(): string;
    }

    interface ContextMenuItem {
        id?: string;
        label?: string;
        handler?: (() => void) | string;
        submenu?: ContextMenuItem[];
        isSelected?: boolean;
        class?: string;
    }

    interface ChartData {
        element: any;
        columns?: Member[];
        id?: string;
        label?: string;
        measure?: MeasureObject;
        rows?: Member[];
        value?: number;
    }

    interface ChartLegendItemData {
        label?: string;
        color?: string;
        tuple?: Member[];
        member?: Member;
        measure?: MeasureObject;
        level?: number;
        isExpanded?: boolean;
        isCollapsed?: boolean;
        isDrilledUp?: boolean;
        isDrilledDown?: boolean;
    }

    interface FlatSort {
        sort: string;
        uniqueName: string;
    }

    interface FieldsListSortingItem {
        caption: string,
        isFolder: boolean,
        isCalculated?: boolean,
        isMeasureFolder?: boolean,
        isKPI?: boolean,
        folder?: string
    }

    interface Toolbar {
        getTabs: () => ToolbarTab[];
        // Connect tab
        connectLocalCSVHandler: () => void;
        connectLocalJSONHandler: () => void;
        connectRemoteCSV: () => void;
        connectRemoteJSON: () => void;
        connectOLAP: () => void;
        // Open tab
        openLocalReport: () => void;
        openRemoteReport: () => void;
        // Save tab
        saveHandler: () => void;
        // Export tab
        printHandler: () => void;
        exportHandler: (type: string) => void;
        // Grid tab
        gridHandler: () => void;
        // Charts tab
        chartsHandler: (type: string) => void;
        chartsMultipleHandler: () => void;
        // Format tab
        formatCellsHandler: () => void;
        conditionalFormattingHandler: () => void;
        // Options tab
        optionsHandler: () => void;
        // Fields tab
        fieldsHandler: () => void;
        // Fullscreen tab
        fullscreenHandler: () => void;
        icons: {
            connect: string,
            connect_csv: string,
            connect_csv_remote: string,
            connect_json_remote: string,
            connect_olap: string,
            open: string,
            open_local: string,
            open_remote: string,
            save: string,
            export: string,
            share: string,
            export_print: string,
            export_html: string,
            export_csv: string,
            export_excel: string,
            export_image: string,
            export_pdf: string,
            grid: string,
            charts: string,
            charts_bar: string,
            charts_line: string,
            charts_scatter: string,
            charts_pie: string,
            charts_stacked_column: string,
            charts_column_line: string,
            format: string,
            format_number: string,
            format_conditional: string,
            options: string,
            fields: string,
            fullscreen: string,
            minimize: string
        };
        showShareReportTab?: boolean;
    }

    interface ToolbarTab {
        android?: boolean;
        args?: any;
        handler?: (() => void) | string;
        icon?: string;
        id: string;
        ios?: boolean;
        mobile?: boolean;
        menu?: ToolbarTab[];
        rightGroup?: boolean;
        title?: string;
        visible?: boolean;
    }

    interface GetDataValueObject {
        data: object[];
        meta: object;
    }

    interface GetDataErrorObject {
        dataHeight: number;
        dataWidth: number;
        errorMessage: string;
    }

    interface Level {
        caption: string;
        uniqueName: string;
    }

    type UnauthorizedErrorHandler = (result: UnauthorizedErrorHandlerResult) => void;
    interface UnauthorizedErrorHandlerResult {
        requestHeaders: {
            [header: string]: string | number;
        }
    }

    interface ErrorEvent {
        error?: string;
    }
}
