# AgentHarness

A Phalanx starter app for building terminal agent harnesses. OpenSwoole-native, supervised by Aegis, rendered by Theatron.

## Run

```bash
composer install
php bin/harness
```

## Shape

- `app/Agents/Assistant/Agent.php` and `prompt.md` are colocated so each agent owns its identity and instructions.
- `app/Config/AppConfig.php` is the typed config root.

## Theatron: Reactive Terminal UI

The harness TUI is built with Theatron -- a reactive component system for the terminal. Components are pure render functions that return an immutable TDOM (Terminal DOM) tree. Reactive state is tracked automatically during render.

### Reactive Primitives

```php
$count = new Signal(0);
$label = new Signal('Idle');

// Derived value -- recomputes when dependencies change
$summary = new Computed(static fn() =>
    "{$label->get()}: {$count->get()} tasks"
);

// Side effect -- fires when the watched value changes
new Watch(
    static fn() => $count->get(),
    static fn(int $new) => $label->set($new > 10 ? 'Busy' : 'Idle'),
);

$count->set(5); // summary recomputes, watch fires
```

### Components

Components implement `__invoke(RenderContext): Renderable`. Any signal read during render becomes a tracked dependency -- when it changes, only that component re-renders.

```php
final class AgentDashboard implements Component
{
    public function __construct(
        private(set) Signal $tasks = new Signal(3),
        private(set) Signal $status = new Signal('idle'),
    ) {}

    public function __invoke(RenderContext $ctx): Renderable
    {
        $tasks = $this->tasks->get();
        $status = $this->status->get();

        return column(
            panel('[bold]Olympus[/bold]', column(
                row(
                    text('[cyan]Themistocles[/cyan]')->size(Size::fr(1)),
                    text("[yellow]{$status}[/yellow]")->align(Align::Right),
                ),
                divider(),
                text("Active tasks: {$tasks}"),
                progress($tasks / 10, 'Completion'),
                spinner('Processing...'),
            ))->border(Border::Rounded),

            statusLine(
                text(' 2 alerts ')->background(Color::named('red')),
                text(" {$status} ")->color(Color::named('green')),
            ),
        );
    }
}
```

### Composition

`mount()` composes child components into the TDOM tree. Each mounted component owns its reactive lifecycle -- signals created inside it are disposed when the component unmounts.

```php
final class Shell implements Component
{
    public function __invoke(RenderContext $ctx): Renderable
    {
        return row(
            mount(Sidebar::class)->size(Size::fixed(24)),
            mount(AgentDashboard::class)->size(Size::fill()),
        );
    }
}
```

### Layout

```php
Size::fill()              // fill available space
Size::fixed(24)           // exactly 24 cells
Size::fr(2)               // fractional units (2/total of remainder)
Size::percent(50)         // 50% of container

Border::Rounded           // ╭─╮│╰─╯
Border::Heavy             // ┏━┓┃┗━┛

Align::Left | Align::Center | Align::Right

Padding::all(1)           // 1 cell on all sides
Padding::h(2)->v(1)       // horizontal 2, vertical 1
```

### Text Styling

BBCode-style markup is parsed automatically using the active theme:

```php
text('[bold]Hello[/bold] [red]world[/red]')
text('[cyan]Agent[/cyan] — [dim]idle[/dim]')
```

Colors support named, indexed, RGB, and hex:

```php
Color::named('cyan')
Color::hex('#1a1a2e')
Color::rgb(255, 100, 50)
```
