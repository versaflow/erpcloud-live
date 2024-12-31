const TextFieldComponent = Formio.Components.components.textfield;
class ColorPickerComponent extends TextFieldComponent {
  static schema(...extend) {
    return TextFieldComponent.schema({
      type: 'colorpicker',
      label: 'Color',
      key: 'colorpicker',
      protected: true,
      tableView: false,
    }, ...extend);
  }

  static get builderInfo() {
    return {
      title: 'Color',
      icon: 'asterisk',
      group: 'basic',
      documentation: '/userguide/#',
      weight: 40,
      schema: ColorPickerComponent.schema()
    };
  }

  get defaultSchema() {
    return _.omit(ColorPickerComponent.schema(), ['protected', 'tableView']);
  }

  get inputInfo() {
    const info = super.inputInfo;
    info.attr.type = 'color';
    return info;
  }
}