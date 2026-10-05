CREATE TABLE "sessions" (
	"id" varchar(64) PRIMARY KEY NOT NULL,
	"user_id" integer NOT NULL,
	"expires_at" timestamp with time zone NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "users" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "users_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"name" varchar(255) NOT NULL,
	"email" varchar(255) NOT NULL,
	"password" text NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "users_email_unique" UNIQUE("email")
);
--> statement-breakpoint
CREATE TABLE "ticker_items" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "ticker_items_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"text" varchar(300) NOT NULL,
	"link" varchar(255) DEFAULT '' NOT NULL,
	"active" boolean DEFAULT true NOT NULL,
	"sort_order" integer DEFAULT 0 NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "videos" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "videos_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"title" varchar(200) DEFAULT '' NOT NULL,
	"url" varchar(255) NOT NULL,
	"sort_order" integer DEFAULT 0 NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "listing_images" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "listing_images_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"listing_id" integer NOT NULL,
	"path" varchar(255) NOT NULL,
	"thumb_path" varchar(255) DEFAULT '' NOT NULL,
	"sort_order" integer DEFAULT 0 NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "listings" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "listings_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"type" varchar(20) NOT NULL,
	"title" varchar(200) NOT NULL,
	"slug" varchar(220) NOT NULL,
	"location" varchar(255) NOT NULL,
	"price" varchar(255) DEFAULT '' NOT NULL,
	"call_for_price" boolean DEFAULT false NOT NULL,
	"land_area" varchar(255) DEFAULT '' NOT NULL,
	"built_area" varchar(255) DEFAULT '' NOT NULL,
	"size" varchar(255) DEFAULT '' NOT NULL,
	"bedrooms" integer DEFAULT 0 NOT NULL,
	"baths" integer DEFAULT 0 NOT NULL,
	"floor" varchar(255) DEFAULT '' NOT NULL,
	"video_url" varchar(255) DEFAULT '' NOT NULL,
	"facilities" jsonb DEFAULT '[]'::jsonb NOT NULL,
	"description" text DEFAULT '' NOT NULL,
	"featured" boolean DEFAULT false NOT NULL,
	"featured_order" integer DEFAULT 0 NOT NULL,
	"published" boolean DEFAULT false NOT NULL,
	"masterplan_id" integer,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "listings_type_slug_unique" UNIQUE("type","slug")
);
--> statement-breakpoint
CREATE TABLE "masterplans" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "masterplans_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"name" varchar(150) NOT NULL,
	"slug" varchar(170) NOT NULL,
	"description" text DEFAULT '' NOT NULL,
	"image_path" varchar(255) DEFAULT '' NOT NULL,
	"image_width" integer DEFAULT 0 NOT NULL,
	"image_height" integer DEFAULT 0 NOT NULL,
	"published" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "masterplans_slug_unique" UNIQUE("slug")
);
--> statement-breakpoint
CREATE TABLE "plots" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "plots_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"masterplan_id" integer NOT NULL,
	"number" varchar(50) NOT NULL,
	"size" varchar(255) DEFAULT '' NOT NULL,
	"price" varchar(255) DEFAULT '' NOT NULL,
	"position" varchar(255) DEFAULT '' NOT NULL,
	"status" varchar(20) DEFAULT 'available' NOT NULL,
	"polygon" jsonb NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "settings" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "settings_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"key" varchar(100) NOT NULL,
	"value" text NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "settings_key_unique" UNIQUE("key")
);
--> statement-breakpoint
ALTER TABLE "sessions" ADD CONSTRAINT "sessions_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "listing_images" ADD CONSTRAINT "listing_images_listing_id_listings_id_fk" FOREIGN KEY ("listing_id") REFERENCES "public"."listings"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "listings" ADD CONSTRAINT "listings_masterplan_id_masterplans_id_fk" FOREIGN KEY ("masterplan_id") REFERENCES "public"."masterplans"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "plots" ADD CONSTRAINT "plots_masterplan_id_masterplans_id_fk" FOREIGN KEY ("masterplan_id") REFERENCES "public"."masterplans"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
CREATE INDEX "listings_type_idx" ON "listings" USING btree ("type");--> statement-breakpoint
CREATE INDEX "listings_location_idx" ON "listings" USING btree ("location");--> statement-breakpoint
CREATE INDEX "listings_featured_idx" ON "listings" USING btree ("featured");--> statement-breakpoint
CREATE INDEX "plots_status_idx" ON "plots" USING btree ("status");